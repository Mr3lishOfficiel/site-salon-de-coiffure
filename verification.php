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

// Utilisation de l'email pour récupérer les informations de l'utilisateur depuis la base de données
$email_utilisateur = $_SESSION["email_utilisateur"];
$sql = "SELECT id_utilisateur, nom, etat FROM Utilisateurs WHERE email = '$email_utilisateur'";
$result = $conn->query($sql);

// Vérification si une ligne a été trouvée
if ($result->num_rows > 0) {
    // Récupération de l'id et du nom de l'utilisateur
    $row = $result->fetch_assoc();
    $id_utilisateur = $row["id_utilisateur"];
    $nom_utilisateur = $row["nom"];
    $etat_utilisateur = $row["etat"];
} else {
    // Si aucun utilisateur correspondant n'est trouvé, vous pouvez gérer cela en conséquence
    $nom_utilisateur = "Utilisateur inconnu";
    $id_utilisateur = "Inconnu";
    $etat_utilisateur = "non_verifie";
}

// Traitement de l'envoi du formulaire
if (isset($_POST["envoyer_code"])) {
    $name = htmlspecialchars($nom_utilisateur);
    $email = htmlspecialchars($email_utilisateur);
    $body = "Votre code de vérification est le numéro suivant : $id_utilisateur";
    
    // Appel au script Python pour envoyer l'e-mail
    $command = escapeshellcmd("python3 /usr/lib/cgi-bin/mail.py '$name' '$email' '$body'");
    $output = shell_exec($command);
    
    if (strpos($output, 'Email envoyé avec succès') !== false) {
        $envoi_message = "Le code a été envoyé avec succès.";
    } else {
        $envoi_message = "Erreur lors de l'envoi de l'e-mail.";
    }
}

// Traitement de la vérification du code
if (isset($_POST["verifier_code"])) {
    $code_saisi = $_POST["code"];
    if ($code_saisi == $id_utilisateur) {
        // Mettre à jour l'état de l'utilisateur dans la base de données
        $update_sql = "UPDATE Utilisateurs SET etat = 'verifie' WHERE id_utilisateur = '$id_utilisateur'";
        if ($conn->query($update_sql) === TRUE) {
            $verification_message = "Code vérifié avec succès.";
            $etat_utilisateur = 'verifie';

            // Redirection vers accueil.php
            header("Location: accueil.php");
            exit();
        } else {
            $verification_message = "Erreur lors de la mise à jour de l'état : " . $conn->error;
        }
    } else {
        $verification_message = "Code incorrect. Veuillez réessayer.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-image: url('wallpaper.png');
            background-size: cover;
        }
        .container {
            display: flex;
            justify-content: space-between;
            width: 80%;
            max-width: 1200px;
        }
        .form-container {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 10px;
            width: 45%;
        }
        .form-container form {
            display: flex;
            flex-direction: column;
        }
        .form-container label {
            margin-top: 10px;
        }
        .form-container input {
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-container input[readonly] {
            background-color: #f5f5f5;
        }
        .form-container button {
            margin-top: 20px;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background-color: black;
            color: white;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: grey;
        }
        .alert {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($etat_utilisateur == 'verifie'): ?>
            <div class="form-container">
                <h1>Utilisateur Vérifié</h1>
                <p>Vous êtes déjà vérifié.</p>
                <a href="accueil.php"><button>Revenir à l'accueil</button></a>
            </div>
        <?php else: ?>
            <div class="form-container">
                <h1>Formulaire de Contact</h1>
                <form method="post" action="">
                    <label for="name">Nom:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($nom_utilisateur); ?>" readonly><br><br>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email_utilisateur); ?>" readonly><br><br>
                    <input type="hidden" id="body" name="body" value="Votre code de vérification est le numéro suivant : <?php echo $id_utilisateur; ?>">
                    <button type="submit" name="envoyer_code">Envoyer</button>
                </form>
                <?php if (isset($envoi_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $envoi_message; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-container">
                <h1>Vérification de l'utilisateur</h1>
                <form method="post" action="">
                    <label for="code">Code:</label>
                    <input type="text" id="code" name="code" required><br><br>
                    <button type="submit" name="verifier_code">Vérifier</button>
                </form>
                <?php if (isset($verification_message)): ?>
                    <div class="alert <?php echo $code_saisi == $id_utilisateur ? 'alert-success' : 'alert-danger'; ?>">
                        <?php echo $verification_message; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

