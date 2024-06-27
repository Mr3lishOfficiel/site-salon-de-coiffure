<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "comptable") {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "salon";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connexion échouée: " . $conn->connect_error);
}

$factures_query = "SELECT * FROM Factures";
$factures_result = $conn->query($factures_query);

$chiffre_affaire_mois_query = "
    SELECT SUM(montant_total) as total 
    FROM Factures 
    WHERE MONTH(date_facturation) = MONTH(CURRENT_DATE()) 
    AND YEAR(date_facturation) = YEAR(CURRENT_DATE())";
$chiffre_affaire_mois_result = $conn->query($chiffre_affaire_mois_query);
$chiffre_affaire_mois = $chiffre_affaire_mois_result->fetch_assoc()['total'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Visualisation des Factures - Salon de Coiffure</title>
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
            font-size: 20px;
        }
        .btn-black:hover {
            background-color: grey;
        }
        .container {
            margin-top: 110px;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: black;
            color: white;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Salon de coiffure - Comptabilité</h1>
        <div class="navbar-right">
            <a class="btn-black" href="comptable.php">Accueil Panel</a>
            <button class="btn-black" onclick="window.location.href='login.php'">Déconnexion</button>
        </div>
    </div>

    <div class="container">
        <h1>Visualisation des Factures</h1>
        <h2>Chiffre d'affaires du mois : <?php echo number_format($chiffre_affaire_mois, 2, ',', ' '); ?> €</h2>
        <table>
            <thead>
                <tr>
                    <th>ID Facture</th>
                    <th>ID Rendez-vous</th>
                    <th>Montant Total</th>
                    <th>Date de Facturation</th>
                    <th>Détails des Prestations</th>
                    <th>Prénom du Coiffeur</th>
                    <th>État</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $factures_result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['id_facture']; ?></td>
                    <td><?php echo $row['id_rendez_vous']; ?></td>
                    <td><?php echo $row['montant_total']; ?></td>
                    <td><?php echo $row['date_facturation']; ?></td>
                    <td><?php echo $row['prestations_details']; ?></td>
                    <td><?php echo $row['prenom_coiffeur']; ?></td>
                    <td><?php echo $row['etat']; ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <script>
    setTimeout(function() {
        window.location.href = 'login.php';
    }, 120000);
</script>
</body>
</html>

