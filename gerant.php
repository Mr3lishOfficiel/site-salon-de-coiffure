<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrage de la session
session_start();

// Vérification du rôle de l'utilisateur
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "gerant") {
    // Redirection vers la page d'accueil si l'utilisateur n'est pas un gérant
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Salon de Coiffure</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('/wallpaper.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100%;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .container {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 20px;
            text-align: center;
            flex: 1;
            overflow-y: auto;
        }
        .navbar {
            width: 100%;
            background-color: #000;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 3px;
        }
        .navbar a {
            color: #fff;
        }
        .navbar .navbar-right {
            display: flex;
            align-items: center;
        }
        .custom-btn, .btn-black {
            display: inline-block;
            padding: 10px 20px;
            background-color: #000;
            color: #fff;  /* Set text color to white */
            font-size: 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            margin-top: 20px;
            margin-right: 10px;
        }
        .custom-btn:hover, .btn-black:hover {
            background-color: #333;
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
        h1 {
            margin-top: 5mm; /* 5 millimètres d'espacement au-dessus du h1 */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <a class="navbar-brand" href="#">Salon de Coiffure</a>
        <div class="navbar-right">
            <form method="post" style="display: inline;">
                <button type="submit" name="logout" class="btn btn-black">Déconnexion</button>
            </form>
            <form method="post" style="display: inline;">
                <button type="submit" name="delete_account" class="btn btn-black">Supprimer le compte</button>
            </form>
            <button type="button" name="change_password" class="btn btn-black">Modifier mot de passe</button>
        </div>
    </nav>
    <div class="container mt-5">
        <img src="logo.png" alt="Logo" style="display: block; margin: 0 auto; margin-bottom: 5mm;">
        <h1>Administration du salon</h1>
        <a href="caisse_gerant.php" class="custom-btn">Accès à la caisse</a>
        <a href="panel_gerant.php" class="custom-btn">Accès au panel Gérant</a>
        <a href="dashboard.php" class="custom-btn">Tableau de bord</a>
    </div>

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
    <script>
        $(document).ready(function() {
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
