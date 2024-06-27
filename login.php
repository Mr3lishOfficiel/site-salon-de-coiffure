<?php
// Démarrage de la session
session_start();

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "salon";

function connectDB() {
    global $servername, $username, $password, $dbname;
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("La connexion à la base de données a échoué : " . $conn->connect_error);
    }
    return $conn;
}

// Fonction de connexion
function login($email, $mot_de_passe) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT id_utilisateur, email, mot_de_passe_hashé, role FROM Utilisateurs WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($mot_de_passe, $row["mot_de_passe_hashé"])) {
            return $row["role"]; // Retourne le rôle de l'utilisateur en cas de succès
        }
    }
    
    return false; // Retourne false si l'authentification échoue
}

// Traitement du formulaire de connexion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $mot_de_passe = $_POST["mot_de_passe"];

    // Vérification des identifiants de connexion
    $role = login($email, $mot_de_passe);
    if ($role) {
        // Authentification réussie, stockage de l'email et du rôle dans la session
        $_SESSION["email_utilisateur"] = $email;
        $_SESSION["role"] = $role;
        
        // Redirection en fonction du rôle de l'utilisateur
        switch ($role) {
            case "client":
                header("Location: accueil.php");
                exit();
            case "gerant":
                header("Location: gerant.php");
                exit();
            case "coiffeur":
                header("Location: caisse.php");
                exit();
            case "comptable":
                header("Location: comptable.php");
                exit();

            // Ajoutez d'autres cas pour d'autres rôles si nécessaire
            default:
                header("Location: accueil.php"); // Redirection par défaut
                exit();
        }
    } else {
        // Mot de passe incorrect ou compte inexistant
        $error_message = "Mot de passe incorrect ou compte inexistant.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Salon de Coiffure</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Styles CSS */
        body {
            background-image: url('/wallpaper.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
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
        .container {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            margin-top: 60px; /* Espace pour la navbar */
        }
        .form-control {
            width: 300px;
            margin: 0 auto;
        }
        .custom-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #000; /* Bouton noir */
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
            background-color: #333; /* Couleur au survol */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <a class="navbar-brand" href="#">Salon de Coiffure</a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Accueil</a>
                </li>
            </ul>
        </div>
    </nav>
    <div class="container">
        <h1>Connexion au Salon de Coiffure</h1>
        <?php if (isset($error_message)) { ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php } ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="email">Adresse email :</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe :</label>
                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <button type="submit" class="custom-btn">Se connecter</button>
        </form>
        <p>Pas encore de compte ? <a href="register.php">Inscrivez-vous ici</a>.</p>
    </div>
</body>
</html>
