<?php
// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "salon";

// Création de la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérification de la connexion
if ($conn->connect_error) {
    die("La connexion à la base de données a échoué : " . $conn->connect_error);
}

// Fonction pour récupérer les rendez-vous pour un jour donné
function getRendezVous($date) {
    global $conn;
    $rendezVous = array();
    $stmt = $conn->prepare("SELECT * FROM Rendez_vous WHERE DATE(date_heure) = ?");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rendezVous[] = $row['date_heure'];
    }
    return $rendezVous;
}

// Jours de la semaine en français
$jours_fr = array(
    'Monday' => 'Lundi',
    'Tuesday' => 'Mardi',
    'Wednesday' => 'Mercredi',
    'Thursday' => 'Jeudi',
    'Friday' => 'Vendredi',
    'Saturday' => 'Samedi',
    'Sunday' => 'Dimanche'
);

// Vérifiez si les paramètres de date sont présents dans l'URL
if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
} else {
    // Si les paramètres de date ne sont pas présents, utilisez la semaine actuelle
    $start_date = date('Y-m-d', strtotime('monday this week'));
    $end_date = date('Y-m-d', strtotime('sunday this week'));
}

// Calculer les jours à afficher
$start_date_timestamp = strtotime($start_date);
$end_date_timestamp = strtotime($end_date);
$days_to_show = 4; // Nombre de jours à afficher

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
        }
        .navbar a {
            color: #fff;
        }
        .custom-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #000;
            color: #fff;
            font-size: 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            margin-top: 20px;
        }
        .custom-btn:hover {
            background-color: #333;
        }
        .week-container {
            display: flex;
            align-items: center;
            gap: 20px;
            justify-content: center;
            margin-top: 20px;
        }
        .day-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 200px;
        }
        .day-heading {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .time-slots {
            list-style-type: none;
            padding: 0;
        }
        .time-slot {
            margin-bottom: 5px;
            padding: 5px 10px;
            border-radius: 5px;
            background-color: #e0e0e0;
            cursor: pointer;
        }
        .time-slot:hover {
            background-color: #c0c0c0;
        }
        .time-slot.used {
            text-decoration: line-through;
            color: #888;
            cursor: not-allowed;
        }
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1; 
            padding-top: 100px; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgb(0,0,0); 
            background-color: rgba(0,0,0,0.4); 
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            text-align: center;
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
        .navigation-btn {
            font-size: 24px;
            cursor: pointer;
            margin: 10px;
        }
        .logo {
            margin-top: 20px;
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <a class="navbar-brand" href="#">Salon de Coiffure</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="login.php">Connexion</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="register.php">Inscription</a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container mt-5">
        <img src="/logo.png" alt="Logo du Salon" class="logo">
        
        <h2 class="mt-5">Planning des Créneaux</h2>
        <div class="week-container">
            <!-- Boutons de navigation -->
            <button class="navigation-btn" onclick="previousDays()">&lt;</button>
            <div id="days-container" style="display: flex; gap: 20px;">
                <!-- Contenu des jours -->
                <?php
                $day_count = 0;
                for ($i = $start_date_timestamp; $i <= $end_date_timestamp && $day_count < $days_to_show; $i = strtotime('+1 day', $i)) {
                    $current_date = date('Y-m-d', $i);
                    $day_name = $jours_fr[date('l', $i)]; // Convertir le jour en français
                    setlocale(LC_TIME, 'fr_FR.UTF-8'); // Définit la localisation en français
                    $day_month = strftime('%e %B', $i); // Jour et mois formatés en français
                    $rendezVous = getRendezVous($current_date); // Récupère les rendez-vous pour la date actuelle
                    echo "<div class='day-container'>";
                    echo "<h3 class='day-heading'>$day_name $day_month</h3>";
                    echo "<ul class='time-slots'>";
                    for ($hour = 9; $hour < 17; $hour++) {
                        $start_time = $current_date . " " . str_pad($hour, 2, '0', STR_PAD_LEFT) . ":00:00";
                        $end_time = $current_date . " " . str_pad($hour + 1, 2, '0', STR_PAD_LEFT) . ":00:00";
                        $creneau = "$hour h à " . ($hour + 1) . " h"; // Format du créneau horaire
                        $class = in_array($start_time, $rendezVous) ? 'used' : ''; // Classe pour les créneaux déjà pris
                        echo "<li class='time-slot $class' onclick=\"handleSlotSelection('$creneau', '$day_name $day_month');\">$creneau</li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                    $day_count++;
                }
                ?>
            </div>
            <button class="navigation-btn" onclick="nextDays()">&gt;</button>
        </div>
    </div>

    <!-- Modal -->
<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <p>Vous devez être connecté pour réserver ce créneau :</p>
        <a id="already-registered" class="custom-btn">Déjà inscrit ?</a>
        <a id="not-registered-yet" class="custom-btn">Pas encore inscrit ?</a>
    </div>
</div>

    <script>
       
        var modal = document.getElementById("myModal");

// Ouvrir le modal
function openModal() {
    modal.style.display = "block";
}

// Fermer le modal
function closeModal() {
    modal.style.display = "none";
}

// Fonction pour ouvrir le modal et définir les actions des boutons
function openModalAndSetActions(creneau, date) {
    openModal();
    // Ajouter les événements aux boutons pour les redirections
    document.getElementById('already-registered').addEventListener('click', function() {
        window.location.href = `login.php`;
    });
    document.getElementById('not-registered-yet').addEventListener('click', function() {
        window.location.href = `register.php`;
    });
}

// Fonction pour gérer l'ouverture du modal avec les informations de créneau
function handleSlotSelection(creneau, date) {
    openModalAndSetActions(creneau, date);
}
        var startDate = new Date("<?php echo $start_date; ?>");
        var daysContainer = document.getElementById("days-container");

        function previousDays() {
            startDate.setDate(startDate.getDate() - <?php echo $days_to_show; ?>);
            updateDays();
        }

        function nextDays() {
            startDate.setDate(startDate.getDate() + <?php echo $days_to_show; ?>);
            updateDays();
        }

        function updateDays() {
            var startDateString = startDate.toISOString().split('T')[0];
            var endDate = new Date(startDate);
            endDate.setDate(endDate.getDate() + <?php echo $days_to_show - 1; ?>);
            var endDateString = endDate.toISOString().split('T')[0];
            window.location.href = `index.php?start_date=${startDateString}&end_date=${endDateString}`;
        }
    </script>
</body>
</html>
