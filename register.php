<?php
require_once 'api.php';

// Fonction pour générer un captcha
function generateCaptcha() {
    $captcha_options = array("1111", "1111", "1111");
    return $captcha_options[array_rand($captcha_options)];
}

// Initialisation de la variable du captcha
$captcha = generateCaptcha();

// Initialisation des messages d'erreur
$erreur_mot_de_passe = '';
$erreur_captcha = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = $_POST["nom"];
    $email = $_POST["email"];
    $mot_de_passe = $_POST["mot_de_passe"];
    $mot_de_passe_confirm = $_POST["mot_de_passe_confirm"];
    $user_captcha = $_POST["captcha"];

    // Vérification du captcha
    if ($user_captcha !== $captcha) {
        // Si le captcha est incorrect, afficher un message d'erreur
        $erreur_captcha = "Le captcha est incorrect.";
    } else {
        // Vérification des mots de passe
        if ($mot_de_passe !== $mot_de_passe_confirm) {
            // Si les mots de passe ne correspondent pas, afficher un message d'erreur
            $erreur_mot_de_passe = "Les mots de passe ne correspondent pas.";
        } elseif (!preg_match('/[A-Z]/', $mot_de_passe) || !preg_match('/[^a-zA-Z\d]/', $mot_de_passe)) {
            // Si le mot de passe ne contient pas au moins une majuscule et un symbole, afficher un message d'erreur
            $erreur_mot_de_passe = "Le mot de passe doit contenir au moins une majuscule et un symbole.";
        } else {
            // Vérification de l'inscription
            if (register($nom, $email, $mot_de_passe)) {
                // Inscription réussie, afficher un popup de confirmation puis rediriger vers la page de connexion après 2 secondes
                echo '<script>alert("Inscription réussie. Redirection vers la page de connexion."); setTimeout(function(){ window.location.href = "login.php"; }, 1000);</script>';
                exit();
            } else {
                // Erreur lors de l'inscription
                echo "Erreur lors de l'inscription.";
            }
        }
    }
}

if (isset($_GET['change_captcha'])) {
    echo generateCaptcha();
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Salon de Coiffure</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
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
        <h1 class="mb-4">Inscription</h1>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <input type="text" class="form-control" name="nom" placeholder="Nom complet" required>
            </div>
            <div class="form-group">
                <input type="email" class="form-control" name="email" placeholder="Adresse e-mail" required>
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="mot_de_passe" id="mot_de_passe" placeholder="Mot de passe" required>
                <button type="button" onclick="togglePassword('mot_de_passe');" class="btn btn-secondary btn-sm">Afficher</button>
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="mot_de_passe_confirm" id="mot_de_passe_confirm" placeholder="Confirmer le mot de passe" required>
                <button type="button" onclick="togglePassword('mot_de_passe_confirm');" class="btn btn-secondary btn-sm">Afficher</button>
            </div>
            <div class="form-group">
                <label for="captcha">Entrez le code suivant : <span id="captcha-code"><?php echo $captcha; ?></span></label>
                <input type="text" class="form-control" name="captcha" placeholder="Code de vérification" required>
                <small class="text-danger"><?php echo $erreur_captcha; ?></small>
            </div>
            <button type="submit" class="custom-btn">S'inscrire</button>
            <small class="text-danger"><?php echo $erreur_mot_de_passe; ?></small>
        </form>
        <p>Déjà inscrit ? <a href="login.php">Connectez-vous ici</a>.</p>
    </div>
    <script>
        // Fonction pour afficher ou masquer le mot de passe
        function togglePassword(inputId) {
            var x = document.getElementById(inputId);
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }

        // Fonction pour changer le captcha toutes les 40 secondes
        function changeCaptcha() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '<?php echo $_SERVER["PHP_SELF"]; ?>?change_captcha=true', true);
            xhr.onload = function() {
                if (this.status === 200) {
                    document.getElementById('captcha-code').innerText = this.responseText;
                }
            };
            xhr.send();
        }

        setInterval(changeCaptcha, 80000); // Changer le captcha toutes les 40 secondes
    </script>
</body>
</html>
