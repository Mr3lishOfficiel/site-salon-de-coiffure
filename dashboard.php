<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION["role"]) || ($_SESSION["role"] !== "coiffeur" && $_SESSION["role"] !== "gerant")) {
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

$non_attribue_query = "SELECT COUNT(*) as count FROM Rendez_vous WHERE etat='non attribué'";
$non_attribue_result = $conn->query($non_attribue_query);
$non_attribue_count = $non_attribue_result->fetch_assoc()['count'];

$termine_query = "SELECT COUNT(*) as count FROM Rendez_vous WHERE etat='terminé'";
$termine_result = $conn->query($termine_query);
$termine_count = $termine_result->fetch_assoc()['count'];

$annule_query = "SELECT COUNT(*) as count FROM Rendez_vous WHERE etat='annulé'";
$annule_result = $conn->query($annule_query);
$annule_count = $annule_result->fetch_assoc()['count'];

$prestations_query = "
    SELECT prestations_details, COUNT(*) as count 
    FROM Factures 
    GROUP BY prestations_details 
    ORDER BY count DESC 
    LIMIT 1";
$prestations_result = $conn->query($prestations_query);
$prestation_populaire = $prestations_result->fetch_assoc();

$toutes_prestations_query = "
    SELECT prestations_details, COUNT(*) as count 
    FROM Factures 
    GROUP BY prestations_details 
    ORDER BY count DESC";
$toutes_prestations_result = $conn->query($toutes_prestations_query);

$rendez_vous_semaine_query = "
    SELECT WEEK(date_heure) as semaine, COUNT(*) as count 
    FROM Rendez_vous 
    GROUP BY semaine";
$rendez_vous_semaine_result = $conn->query($rendez_vous_semaine_query);

$chiffre_affaire_semaine_query = "
    SELECT WEEK(date_facturation) as semaine, SUM(montant_total) as total 
    FROM Factures 
    GROUP BY semaine";
$chiffre_affaire_semaine_result = $conn->query($chiffre_affaire_semaine_query);

$chiffre_affaire_mois_query = "
    SELECT MONTH(date_facturation) as mois, SUM(montant_total) as total 
    FROM Factures 
    GROUP BY mois";
$chiffre_affaire_mois_result = $conn->query($chiffre_affaire_mois_query);

$chiffre_affaire_coiffeur_query = "
    SELECT prenom_coiffeur, SUM(montant_total) as total 
    FROM Factures 
    GROUP BY prenom_coiffeur";
$chiffre_affaire_coiffeur_result = $conn->query($chiffre_affaire_coiffeur_query);

$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de Bord - Salon de Coiffure</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .chart-container {
            width: 45%;
            height: 300px;
            display: inline-block;
        }
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
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Salon de coiffure</h1>
        <div class="navbar-right">
            <a class="btn-black" href="gerant.php">Accueil Panel</a>
            <button class="btn-black" onclick="window.location.href='login.php'">Déconnexion</button>
        </div>
    </div>
<h1 style="margin-top: 110px;">Tableau de Bord - Salon de Coiffure</h1>

<h2>Rendez-vous non attribués : <?php echo $non_attribue_count; ?></h2>
<h2>Rendez-vous terminés : <?php echo $termine_count; ?></h2>
<h2>Rendez-vous annulés : <?php echo $annule_count; ?></h2>

<h2>Prestation la plus prise : <?php echo $prestation_populaire['prestations_details']; ?> (<?php echo $prestation_populaire['count']; ?> fois)</h2>

<div class="chart-container">
    <h2>Rendez-vous par semaine</h2>
    <canvas id="rendezVousSemaineChart"></canvas>
</div>

<div class="chart-container">
    <h2>Chiffre d'affaire par semaine</h2>
    <canvas id="chiffreAffaireSemaineChart"></canvas>
</div>

<div class="chart-container">
    <h2>Chiffre d'affaire par mois</h2>
    <canvas id="chiffreAffaireMoisChart"></canvas>
</div>

<div class="chart-container">
    <h2>Classement des prestations les plus demandées</h2>
    <canvas id="toutesPrestationsChart"></canvas>
</div>

<div class="chart-container">
    <h2>Chiffre d'affaire par coiffeur</h2>
    <canvas id="chiffreAffaireCoiffeurChart"></canvas>
</div>

<script>
    var rendezVousSemaineLabels = [];
    var rendezVousSemaineData = [];
    <?php
    while($row = $rendez_vous_semaine_result->fetch_assoc()) {
        echo "rendezVousSemaineLabels.push('Semaine " . $row['semaine'] . "');";
        echo "rendezVousSemaineData.push(" . $row['count'] . ");";
    }
    ?>

    var chiffreAffaireSemaineLabels = [];
    var chiffreAffaireSemaineData = [];
    <?php
    while($row = $chiffre_affaire_semaine_result->fetch_assoc()) {
        echo "chiffreAffaireSemaineLabels.push('Semaine " . $row['semaine'] . "');";
        echo "chiffreAffaireSemaineData.push(" . $row['total'] . ");";
    }
    ?>

    var chiffreAffaireMoisLabels = [];
    var chiffreAffaireMoisData = [];
    <?php
    while($row = $chiffre_affaire_mois_result->fetch_assoc()) {
        echo "chiffreAffaireMoisLabels.push('Mois " . $row['mois'] . "');";
        echo "chiffreAffaireMoisData.push(" . $row['total'] . ");";
    }
    ?>

    var toutesPrestationsLabels = [];
    var toutesPrestationsData = [];
    <?php
    while($row = $toutes_prestations_result->fetch_assoc()) {
        echo "toutesPrestationsLabels.push('" . $row['prestations_details'] . "');";
        echo "toutesPrestationsData.push(" . $row['count'] . ");";
    }
    ?>

    var chiffreAffaireCoiffeurLabels = [];
    var chiffreAffaireCoiffeurData = [];
    <?php
    while($row = $chiffre_affaire_coiffeur_result->fetch_assoc()) {
        echo "chiffreAffaireCoiffeurLabels.push('" . $row['prenom_coiffeur'] . "');";
        echo "chiffreAffaireCoiffeurData.push(" . $row['total'] . ");";
    }
    ?>

    var ctx1 = document.getElementById('rendezVousSemaineChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: rendezVousSemaineLabels,
            datasets: [{
                label: 'Rendez-vous',
                data: rendezVousSemaineData,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    var ctx2 = document.getElementById('chiffreAffaireSemaineChart').getContext('2d');
    new Chart(ctx2, {
        type: 'line',
        data: {
            labels: chiffreAffaireSemaineLabels,
            datasets: [{
                label: 'Chiffre d\'affaire',
                data: chiffreAffaireSemaineData,
                backgroundColor: 'rgba(153, 102, 255, 0.2)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    var ctx3 = document.getElementById('chiffreAffaireMoisChart').getContext('2d');
    new Chart(ctx3, {
        type: 'line',
        data: {
            labels: chiffreAffaireMoisLabels,
            datasets: [{
                label: 'Chiffre d\'affaire',
                data: chiffreAffaireMoisData,
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    var ctx4 = document.getElementById('toutesPrestationsChart').getContext('2d');
    new Chart(ctx4, {
        type: 'pie',
        data: {
            labels: toutesPrestationsLabels,
            datasets: [{
                label: 'Prestations',
                data: toutesPrestationsData,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        }
    });

    var ctx5 = document.getElementById('chiffreAffaireCoiffeurChart').getContext('2d');
    new Chart(ctx5, {
        type: 'bar',
        data: {
            labels: chiffreAffaireCoiffeurLabels,
            datasets: [{
                label: 'Chiffre d\'affaire',
                data: chiffreAffaireCoiffeurData,
                backgroundColor: 'rgba(255, 206, 86, 0.2)',
                borderColor: 'rgba(255, 206, 86, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
</body>
</html>
