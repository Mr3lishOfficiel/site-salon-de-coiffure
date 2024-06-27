<?php
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
    $stmt = $conn->prepare("SELECT id_utilisateur, email, mot_de_passe_hashé FROM Utilisateurs WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($mot_de_passe, $row["mot_de_passe_hashé"])) {
            return $row["id_utilisateur"]; // Retourne l'ID de l'utilisateur en cas de succès
        }
    }
    
    return false; // Retourne false si l'authentification échoue
}

// Fonction d'inscription
function register($nom, $email, $mot_de_passe) {
    $conn = connectDB();
    $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT); // Hashage du mot de passe

    $stmt = $conn->prepare("INSERT INTO Utilisateurs (nom, email, mot_de_passe_hashé, role) VALUES (?, ?, ?, 'client')");
    $stmt->bind_param("sss", $nom, $email, $hashed_password);
    
    if ($stmt->execute()) {
        return true; // Retourne true si l'inscription réussit
    } else {
        return false; // Retourne false si l'inscription échoue
    }
}
?>
