<?php
// Démarrage de la session
session_start();

// Afficher les erreurs (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérification du rôle de l'utilisateur (doit être "coiffeur" ou "gerant")
if (!isset($_SESSION["role"]) || ($_SESSION["role"] !== "coiffeur" && $_SESSION["role"] !== "gerant")) {
    header("Location: login.php");
    exit();
}

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "salon";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("La connexion à la base de données a échoué : " . $conn->connect_error);
}

// Fonction pour récupérer les rendez-vous
function getAllRendezVous() {
    global $conn;
    $rendezvous = array();
    $stmt = $conn->prepare("SELECT r.id_rendez_vous, r.date_heure, uc.nom AS client_nom, u.nom AS coiffeur_nom, p.nom_prestation, p.tarif, r.id_prestation, r.etat
                            FROM Rendez_vous r
                            JOIN Utilisateurs uc ON r.id_utilisateur = uc.id_utilisateur
                            LEFT JOIN Utilisateurs u ON r.id_coiffeur = u.id_utilisateur AND u.role = 'coiffeur'
                            JOIN Prestations p ON r.id_prestation = p.id_prestation
                            WHERE r.etat != 'terminé'");
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rendezvous[] = $row;
        }
    } else {
        echo "Erreur lors de la récupération des rendez-vous : " . $stmt->error;
    }
    $stmt->close();
    return $rendezvous;
}

// Fonction pour récupérer les prestations
function getPrestations() {
    global $conn;
    $prestations = array();
    $stmt = $conn->prepare("SELECT id_prestation, nom_prestation, tarif FROM Prestations");
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $prestations[] = $row;
        }
    } else {
        echo "Erreur lors de la récupération des prestations : " . $stmt->error;
    }
    $stmt->close();
    return $prestations;
}

// Fonction pour récupérer les coiffeurs
function getCoiffeurs() {
    global $conn;
    $coiffeurs = array();
    $stmt = $conn->prepare("SELECT id_utilisateur, nom FROM Utilisateurs WHERE role = 'coiffeur'");
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $coiffeurs[] = $row;
        }
    } else {
        echo "Erreur lors de la récupération des coiffeurs : " . $stmt->error;
    }
    $stmt->close();
    return $coiffeurs;
}

// Fonction pour récupérer toutes les factures
function getAllFactures() {
    global $conn;
    $factures = array();
    $stmt = $conn->prepare("SELECT f.id_facture, f.id_rendez_vous, f.montant_total, f.date_facturation, f.prestations_details, f.prenom_coiffeur, f.etat,
                                   rv.date_heure, uc.nom AS client_nom
                            FROM Factures f
                            JOIN Rendez_vous rv ON f.id_rendez_vous = rv.id_rendez_vous
                            JOIN Utilisateurs uc ON rv.id_utilisateur = uc.id_utilisateur");
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $factures[] = $row;
        }
    } else {
        echo "Erreur lors de la récupération des factures : " . $stmt->error;
    }
    $stmt->close();
    return $factures;
}

// Fonction pour calculer le total des prestations pour un rendez-vous avec remise
function calculateTotal($id_rendez_vous, $remise) {
    global $conn;
    $total = 0;
    $stmt = $conn->prepare("SELECT p.tarif FROM Rendez_vous r
                            JOIN Prestations p ON r.id_prestation = p.id_prestation
                            WHERE r.id_rendez_vous = ?");
    $stmt->bind_param("i", $id_rendez_vous);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $total += $row['tarif'];
        }
    } else {
        echo "Erreur lors du calcul du total : " . $stmt->error;
    }
    $stmt->close();

    // Calculer la remise
    $total = $total * ((100 - $remise) / 100);

    return $total;
}

// Traitement du formulaire pour enregistrer la facturation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enregistrer_facture'])) {
    $id_rendez_vous = $_POST['id_rendez_vous'];
    $prenom_coiffeur = $_POST['prenom_coiffeur'];
    $remise = $_POST['remise'];
    $total = calculateTotal($id_rendez_vous, $remise);
    $date_facturation = date('Y-m-d');

    // Récupérer les détails des prestations
    $details_prestations = '';
    $stmt = $conn->prepare("SELECT p.nom_prestation, p.tarif FROM Rendez_vous r
                            JOIN Prestations p ON r.id_prestation = p.id_prestation
                            WHERE r.id_rendez_vous = ?");
    $stmt->bind_param("i", $id_rendez_vous);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $details = array();
        while ($row = $result->fetch_assoc()) {
            $details[] = $row['nom_prestation'] . ' (' . $row['tarif'] . ' €)';
        }
        $details_prestations = implode(', ', $details);
    } else {
        echo "Erreur lors de la récupération des détails des prestations : " . $stmt->error;
    }
    $stmt->close();

    // Mise à jour du rendez-vous avant la facturation
    $stmt = $conn->prepare("UPDATE Rendez_vous SET id_coiffeur = (SELECT id_utilisateur FROM Utilisateurs WHERE nom = ? AND role = 'coiffeur'), etat = 'terminé' WHERE id_rendez_vous = ?");
    $stmt->bind_param("si", $prenom_coiffeur, $id_rendez_vous);
    if (!$stmt->execute()) {
        echo "Erreur lors de la mise à jour du rendez-vous : " . $stmt->error;
    }

    $stmt = $conn->prepare("INSERT INTO Factures (id_rendez_vous, montant_total, date_facturation, prestations_details, prenom_coiffeur, etat) VALUES (?, ?, ?, ?, ?, 'terminé')");
    $stmt->bind_param("idsss", $id_rendez_vous, $total, $date_facturation, $details_prestations, $prenom_coiffeur);
    if (!$stmt->execute()) {
        echo "Erreur lors de l'enregistrement de la facture : " . $stmt->error;
    } else {
        echo "<p>Facture enregistrée avec succès !</p>";
    }
    $stmt->close();
}

// Traitement du formulaire pour modifier l'état d'un rendez-vous
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['modifier_rdv'])) {
    $id_rendez_vous = $_POST['id_rendez_vous'];
    $etat = $_POST['etat'];

    $stmt = $conn->prepare("UPDATE Rendez_vous SET etat = ? WHERE id_rendez_vous = ?");
    $stmt->bind_param("si", $etat, $id_rendez_vous);
    if (!$stmt->execute()) {
        echo "Erreur lors de la mise à jour de la prestation : " . $stmt->error;
    } else {
        echo "<p>État mis à jour avec succès !</p>";
    }
    $stmt->close();
}

// Traitement du formulaire pour modifier la prestation d'un rendez-vous
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['modifier_prestation_rdv'])) {
    $id_rendez_vous = $_POST['id_rendez_vous'];
    $id_prestation = $_POST['id_prestation'];

    $stmt = $conn->prepare("UPDATE Rendez_vous SET id_prestation = ? WHERE id_rendez_vous = ?");
    $stmt->bind_param("ii", $id_prestation, $id_rendez_vous);
    if (!$stmt->execute()) {
        echo "Erreur lors de la mise à jour de la prestation : " . $stmt->error;
    } else {
        echo "<p>Prestation mise à jour avec succès !</p>";
    }
    $stmt->close();
}

// Traitement du formulaire pour supprimer un rendez-vous
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['supprimer_rdv'])) {
    $id_rendez_vous = $_POST['id_rendez_vous'];

    $stmt = $conn->prepare("DELETE FROM Rendez_vous WHERE id_rendez_vous = ?");
    $stmt->bind_param("i", $id_rendez_vous);
    if (!$stmt->execute()) {
        echo "Erreur lors de la suppression du rendez-vous : " . $stmt->error;
    } else {
        echo "<p>Rendez-vous supprimé avec succès !</p>";
    }
    $stmt->close();
}

// Récupérer les rendez-vous et les factures
$tous_les_rendezvous = getAllRendezVous();
$prestations = getPrestations();
$coiffeurs = getCoiffeurs();
$toutes_les_factures = getAllFactures();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Caisse - Salon de coiffure</title>
    <style>
    .navbar {
        background-color: black;
        color: white;
        padding: 8px;
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
    .btn-black {
        background-color: black;
        color: white;
        border: none;
        padding: 20px 30px;
        cursor: pointer;
        font-size: 20px; /* Vous pouvez augmenter cette valeur pour agrandir le texte */
    }
    .btn-black:hover {
        background-color: grey;
    }
    table {
        width: 100%;
        border-collapse: collapse;
    }
    table, th, td {
        border: 1px solid black;
    }
    th, td {
        padding: 10px;
        text-align: left;
    }
    th {
        background-color: #f2f2f2;
    }
    .delete-button {
        background-color: red;
        color: white;
        border: none;
        padding: 5px 10px;
        cursor: pointer;
    }
    </style>
    <script>
        function confirmDeletion(form) {
            if (confirm("Êtes-vous sûr de vouloir supprimer ce rendez-vous ?")) {
                form.submit();
            }
        }
    </script>
    <script>
    setTimeout(function() {
        window.location.href = 'login.php';
    }, 120000);
</script>



</head>
<body>
    <div class="navbar">
        <h1>Salon de coiffure</h1>
        <div class="navbar-right">
    <button class="btn-black" onclick="window.location.href='login.php'">Déconnexion</button>
</div>
    </div>
    <h2 style="margin-top: 110px;">Tous les rendez-vous</h2>
    <table>
        <thead>
            <tr>
                <th>Date et Heure</th>
                <th>Client</th>
                <th>Coiffeur</th>
                <th>Prestation</th>
                <th>Tarif</th>
                <th>État</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tous_les_rendezvous as $rdv): ?>
                <tr>
                    <td><?= $rdv['date_heure'] ?></td>
                    <td><?= $rdv['client_nom'] ?></td>
                    <td><?= $rdv['coiffeur_nom'] ?></td>
                    <td><?= $rdv['nom_prestation'] ?></td>
                    <td><?= $rdv['tarif'] ?> €</td>
                    <td><?= $rdv['etat'] ?></td>
                    <td>
                        <form method="post" style="display: inline-block;">
                            <input type="hidden" name="id_rendez_vous" value="<?= $rdv['id_rendez_vous'] ?>">
                            <label for="etat">État :</label>
                            <select name="etat">
                                <option value="non attribué" <?= $rdv['etat'] == 'non attribué' ? 'selected' : '' ?>>Non attribué</option>
                                <option value="annulée" <?= $rdv['etat'] == 'annulée' ? 'selected' : '' ?>>Annulée</option>
                                <option value="en cours" <?= $rdv['etat'] == 'en cours' ? 'selected' : '' ?>>En cours</option>
                            </select>
                            <button type="submit" name="modifier_rdv">Mise à jour de l'état</button>
                        </form>
                        <form method="post" style="display: inline-block;">
                            <input type="hidden" name="id_rendez_vous" value="<?= $rdv['id_rendez_vous'] ?>">
                            <label for="id_prestation">Prestation :</label>
                            <select name="id_prestation">
                                <?php foreach ($prestations as $prestation): ?>
                                    <option value="<?= $prestation['id_prestation'] ?>" <?= $rdv['id_prestation'] == $prestation['id_prestation'] ? 'selected' : '' ?>><?= $prestation['nom_prestation'] ?> (<?= $prestation['tarif'] ?> €)</option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="modifier_prestation_rdv">Modifier la prestation</button>
                        </form>
                        <form method="POST">
                        <input type="hidden" name="id_rendez_vous" value="<?php echo $rdv["id_rendez_vous"]; ?>">
                        <button type="submit" name="supprimer_rdv" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce rendez-vous ?');">Supprimer</button>
                    </form>
                    </td>
                    <td>
                        <form method="post" style="display: inline-block;">
                            <input type="hidden" name="id_rendez_vous" value="<?= $rdv['id_rendez_vous'] ?>">
                            <label for="remise">Remise :</label>
                            <select name="remise">
                                <option value="0">0%</option>
                                <option value="5">5%</option>
                                <option value="15">15%</option>
                                <option value="30">30%</option>
                            </select>
                            <label for="prenom_coiffeur">Coiffeur :</label>
                            <select name="prenom_coiffeur">
                                <?php foreach ($coiffeurs as $coiffeur): ?>
                                    <option value="<?= $coiffeur['nom'] ?>"><?= $coiffeur['nom'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="enregistrer_facture">Facturer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Factures</h2>
    <table>
        <thead>
            <tr>
                <th>ID Facture</th>
                <th>Date et Heure</th>
                <th>Client</th>
                <th>Coiffeur</th>
                <th>Prestations</th>
                <th>Montant Total</th>
                <th>Date de Facturation</th>
                <th>État</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($toutes_les_factures as $facture): ?>
                <tr>
                    <td><?= $facture['id_facture'] ?></td>
                    <td><?= $facture['date_heure'] ?></td>
                    <td><?= $facture['client_nom'] ?></td>
                    <td><?= $facture['prenom_coiffeur'] ?></td>
                    <td><?= $facture['prestations_details'] ?></td>
                    <td><?= $facture['montant_total'] ?> €</td>
                    <td><?= $facture['date_facturation'] ?></td>
                    <td><?= $facture['etat'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
