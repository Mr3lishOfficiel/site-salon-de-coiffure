<?php
// Démarrage de la session
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION["email_utilisateur"])) {
    // Redirection vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: login.php");
    exit();
}

// Inclusion du fichier API
require_once 'api.php';

// Connexion à la base de données
$conn = connectDB();

// Utilisation de l'email pour récupérer le nom de l'utilisateur depuis la base de données
$email_utilisateur = $_SESSION["email_utilisateur"];
$sql = "SELECT id_utilisateur, nom FROM Utilisateurs WHERE email = '$email_utilisateur'";
$result = $conn->query($sql);

// Vérification si une ligne a été trouvée
if ($result->num_rows > 0) {
    // Récupération de l'id et du nom de l'utilisateur
    $row = $result->fetch_assoc();
    $id_utilisateur = $row["id_utilisateur"];
    $nom_utilisateur = $row["nom"];
} else {
    // Si aucun utilisateur correspondant n'est trouvé, vous pouvez gérer cela en conséquence
    $nom_utilisateur = "Utilisateur inconnu";
}

// Récupération de l'état de l'utilisateur
$sql_etat = "SELECT etat FROM Utilisateurs WHERE id_utilisateur = '$id_utilisateur'";
$result_etat = $conn->query($sql_etat);

if ($result_etat->num_rows > 0) {
    $row_etat = $result_etat->fetch_assoc();
    $etat_utilisateur = $row_etat["etat"];

    // Vérification de l'état de l'utilisateur
    if ($etat_utilisateur !== "verifie") {
        // Afficher un message ou une alerte expliquant que l'utilisateur ne peut pas prendre rendez-vous
        $message_etat = "Vous ne pouvez pas prendre rendez-vous tant que votre compte n'est pas vérifié.";
        $disable_rdv_button = true; // Variable pour désactiver le bouton de soumission du formulaire
    } else {
        $disable_rdv_button = false; // L'utilisateur peut prendre rendez-vous
    }
} else {
    // Si l'état n'est pas trouvé, vous pouvez gérer cela en conséquence
    $message_etat = "Erreur : état de l'utilisateur non trouvé.";
    $disable_rdv_button = true; // Désactiver par défaut si l'état n'est pas trouvé
}

// Traitement de la suppression du compte
if (isset($_POST["delete_account"])) {
    // Suppression du compte de l'utilisateur
    $sql_delete = "DELETE FROM Utilisateurs WHERE email = '$email_utilisateur'";
    $sql_delete_rdv = "DELETE FROM Rendez_vous WHERE id_utilisateur = '$id_utilisateur'";
    if ($conn->query($sql_delete_rdv) === TRUE) {
        $conn->query($sql_delete);
        // Compte supprimé avec succès, déconnexion de l'utilisateur et redirection vers la page de connexion
        session_unset();     // Suppression des variables de session
        session_destroy();   // Destruction de la session
        header("Location: login.php"); // Redirection vers la page de connexion
        exit();
    } else {
        // Erreur lors de la suppression du compte
        $delete_error_message = "Erreur lors de la suppression du compte : " . $conn->error;
    }
}

// Traitement de la déconnexion de l'utilisateur
if (isset($_POST["logout"])) {
    // Déconnexion de l'utilisateur
    session_unset();     // Suppression des variables de session
    session_destroy();   // Destruction de la session
    header("Location: login.php"); // Redirection vers la page de connexion
    exit();
}

// Traitement de la modification du mot de passe
if (isset($_POST["submit_password"])) {
    $new_password = $_POST["new_password"];
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $sql_update_password = "UPDATE Utilisateurs SET mot_de_passe_hashé='$hashed_password' WHERE id_utilisateur='$id_utilisateur'";

    if ($conn->query($sql_update_password) === TRUE) {
        $message_password = "Mot de passe modifié avec succès.";
    } else {
        $erreur_message_password = "Erreur lors de la modification du mot de passe : " . $conn->error;
    }
}

// Récupération des prestations disponibles
$sql_prestations = "SELECT * FROM Prestations";
$result_prestations = $conn->query($sql_prestations);

// Création des créneaux horaires disponibles
$creneaux_horaires = [];
$heure_debut = 9; // Heure de début des rendez-vous
$heure_fin = 18; // Heure de fin des rendez-vous
for ($heure = $heure_debut; $heure < $heure_fin; $heure++) {
    $creneaux_horaires[] = str_pad($heure, 2, '0', STR_PAD_LEFT) . ':00:00'; // Formatage de l'heure
}

// Formulaire pour prendre un rendez-vous soumis
if (isset($_POST["submit_rdv"])) {
    // Récupération des données du formulaire
    $date = $_POST['date'];
    $heure = $_POST['heure'];
    $prestation = $_POST['prestation'];

    // Création de la date_heure en combinant la date et l'heure sélectionnées
    $date_heure = $date . ' ' . $heure;

    // Vérification si le créneau est disponible
    $sql_check_rdv = "SELECT * FROM Rendez_vous WHERE date_heure = '$date_heure'";
    $result_check_rdv = $conn->query($sql_check_rdv);
    if ($result_check_rdv->num_rows > 0) {
        // Si le créneau est déjà pris, afficher un message d'erreur
        $erreur_message = "Ce créneau horaire est déjà pris. Veuillez en choisir un autre.";
    } else {
        // Sinon, insérer le rendez-vous dans la base de données
        $sql_insert_rdv = "INSERT INTO Rendez_vous (date_heure, id_utilisateur, id_prestation) VALUES ('$date_heure', $id_utilisateur, '$prestation')";

        // Exécution de la requête SQL
        if ($conn->query($sql_insert_rdv) === TRUE) {
            // Si l'insertion réussit, afficher un message de confirmation
            $message = "Rendez-vous enregistré avec succès pour le $date à $heure.";
        } else {
            // Si une erreur se produit, afficher l'erreur
            $erreur_message = "Erreur lors de l'enregistrement du rendez-vous : " . $conn->error;
        }
    }
}

if (isset($_POST["submit_contact"])) {
    $sujet = $_POST['sujet'];
    $contenu = $_POST['contenu'];

    // Utiliser 2>&1 pour capturer les erreurs
    $command = escapeshellcmd("/usr/bin/python3 /usr/lib/cgi-bin/mail2.py '$nom_utilisateur' '$email_utilisateur' '$contenu' 2>&1");
    $output = shell_exec($command);

    if ($output !== null && strpos($output, 'Email envoyé avec succès') !== false) {
        $message_email = "Votre message a été envoyé avec succès.";
    } elseif ($output !== null) {
        // Afficher la sortie complète pour diagnostiquer
        $erreur_message_email = "Erreur lors de l'envoi du message : " . htmlspecialchars($output);
    } else {
        $erreur_message_email = "Erreur lors de l'exécution du script de contact.";
    }
}
// Récupération des créneaux horaires disponibles pour une date donnée
if (isset($_GET['get_creneaux'])) {
    $date = $_GET['date'];
    $creneaux_horaires = [];
    $heure_debut = 9; // Heure de début des rendez-vous
    $heure_fin = 18; // Heure de fin des rendez-vous
    for ($heure = $heure_debut; $heure < $heure_fin; $heure++) {
        $creneaux_horaires[] = [
            'heure' => str_pad($heure, 2, '0', STR_PAD_LEFT) . ':00:00',
            'pris' => false
        ];
    }

    $sql_check_rdv = "SELECT DATE_FORMAT(date_heure, '%H:%i:%s') AS heure FROM Rendez_vous WHERE DATE(date_heure) = '$date'";
    $result_check_rdv = $conn->query($sql_check_rdv);
    while ($row = $result_check_rdv->fetch_assoc()) {
        foreach ($creneaux_horaires as &$creneau) {
            if ($creneau['heure'] === $row['heure']) {
                $creneau['pris'] = true;
                break;
            }
        }
    }

    echo json_encode(['creneaux' => $creneaux_horaires]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Salon de Coiffure</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
    <style>
        body {
            background-image: url('/wallpaper.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .navbar {
            background-color: black;
            color: white;
            padding: 10px;
            width: 100%;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        .navbar .navbar-right {
            position: absolute;
            right: 10px;
            top: 10px;
        }
        .container {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            margin-top: 60px;
            max-width: 800px;
            width: 100%;
        }
        .form-group label {
            font-weight: bold;
        }
        .btn-black {
            background-color: black;
            color: white;
        }
        .btn-black:hover {
            background-color: grey;
        }
        .modal {
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            display: none;
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            text-align: left;
            border-radius: 10px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Bienvenue, <?php echo $nom_utilisateur; ?> !</h1>
        <div class="navbar-right">
            <form method="post" style="display: inline;">
                <button type="submit" name="logout" class="btn btn-black">Déconnexion</button>
            </form>
            <form method="post" style="display: inline;">
                <button type="submit" name="delete_account" class="btn btn-black">Supprimer le compte</button>
            </form>
            <form method="post" style="display: inline;">
                <button type="button" name="change_password" class="btn btn-black">Modifier mot de passe</button>
            </form>
	    <form method="get" action="verification.php" style="display: inline;">
    		<button type="submit" class="btn btn-black">Vérification</button>
           </form>
        </div>
    </div>

    <div class="container">
        <?php if (isset($message)): ?>
            <div class="alert alert-success" role="alert"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if (isset($erreur_message)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $erreur_message; ?></div>
        <?php endif; ?>
        <?php if (isset($message_email)): ?>
            <div class="alert alert-success" role="alert"><?php echo $message_email; ?></div>
        <?php endif; ?>
        <?php if (isset($erreur_message_email)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $erreur_message_email; ?></div>
        <?php endif; ?>
        <?php if (isset($message_password)): ?>
            <div class="alert alert-success" role="alert"><?php echo $message_password; ?></div>
        <?php endif; ?>
        <?php if (isset($erreur_message_password)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $erreur_message_password; ?></div>
        <?php endif; ?>

        <h2>Prendre un rendez-vous</h2>
<?php if (isset($disable_rdv_button) && $disable_rdv_button): ?>
    <p>Vous ne pouvez pas prendre rendez-vous tant que votre compte n'est pas vérifié.</p>
<?php else: ?>
    <form method="post" action="">
        <div class="form-group">
            <label for="date">Date :</label>
            <input type="text" id="datepicker" name="date" class="form-control" required autocomplete="off"/>
        </div>
        <div class="form-group">
            <label for="heure">Heure :</label>
            <select id="heure" name="heure" class="form-control" required>
                <!-- Les options seront ajoutées dynamiquement par jQuery -->
            </select>
        </div>
        <div class="form-group">
            <label for="prestation">Prestation :</label>
            <select id="prestation" name="prestation" class="form-control" required>
                <?php while ($row = $result_prestations->fetch_assoc()): ?>
                    <option value="<?php echo $row['id_prestation']; ?>" data-prix="<?php echo $row['tarif']; ?>">
                        <?php echo htmlspecialchars($row['nom_prestation']); ?> - <?php echo number_format((float)$row['tarif'], 2); ?> €
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" name="submit_rdv" class="btn btn-black" <?php if (isset($disable_rdv_button) && $disable_rdv_button) echo "disabled"; ?>>
            Prendre rendez-vous
        </button>
    </form>
<?php endif; ?>


        <h2>Contactez-nous</h2>
        <form method="POST">
            <div class="form-group">
                <label for="sujet">Sujet :</label>
                <input type="text" class="form-control" id="sujet" name="sujet" required>
            </div>
            <div class="form-group">
                <label for="contenu">Message :</label>
                <textarea class="form-control" id="contenu" name="contenu" rows="5" required></textarea>
            </div>
            <button type="submit" name="submit_contact" class="btn btn-info">Envoyer</button>
        </form>

        <!-- Affichage des messages de succès ou d'erreur -->
        <?php if (isset($message_email)) { echo '<p class="alert alert-success">' . $message_email . '</p>'; } ?>
        <?php if (isset($erreur_message_email)) { echo '<p class="alert alert-danger">' . $erreur_message_email . '</p>'; } ?>

    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Modifier le mot de passe</h2>
            <form id="passwordForm" method="post" action="">
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe :</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe :</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" name="submit_password" class="btn btn-black">Modifier</button>
            </form>
            <div id="passwordError" style="color: red; display: none;">Les mots de passe ne correspondent pas.</div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(document).ready(function() {
            // Script pour le datepicker
            $('#datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                minDate: 0,
                firstDay: 1,
                beforeShowDay: function(date) {
                    var day = date.getDay();
                    return [(day != 1), ''];
                },
                onSelect: function(dateText) {
                    $.ajax({
                        url: '',
                        type: 'GET',
                        data: {
                            get_creneaux: true,
                            date: dateText
                        },
                        success: function(response) {
                            var creneaux = JSON.parse(response).creneaux;
                            var options = '';
                            creneaux.forEach(function(creneau) {
                                if (!creneau.pris) {
                                    options += '<option value="' + creneau.heure + '">' + creneau.heure.substr(0, 5) + '</option>';
                                }
                            });
                            $('#heure').html(options);
                        }
                    });
                }
            });

            // Script pour le popup de modification de mot de passe
            var modal = $('#passwordModal');
            var btn = $("button[name='change_password']");
            var span = $('.close');

            btn.click(function(event) {
                event.preventDefault();
                modal.show();
            });

            span.click(function() {
                modal.hide();
            });

            $(window).click(function(event) {
                if ($(event.target).is(modal)) {
                    modal.hide();
                }
            });

            $('#passwordForm').submit(function(event) {
                var password = $('#new_password').val();
                var confirmPassword = $('#confirm_password').val();

                if (password !== confirmPassword) {
                    event.preventDefault();
                    $('#passwordError').show();
                } else {
                    $('#passwordError').hide();
                }
            });
        });
    </script>
    <script>
    setTimeout(function() {
        window.location.href = 'login.php';
    }, 120000);
</script>

</body>
</html>
