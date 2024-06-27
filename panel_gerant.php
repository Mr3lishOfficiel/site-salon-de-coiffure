<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrage de la session
session_start();

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

// Vérification du rôle de l'utilisateur
if ($_SESSION["role"] !== "gerant") {
    // Redirection vers la page d'accueil si l'utilisateur n'est pas un gérant
    header("Location: accueil.php");
    exit();
}

// Traitement de la déconnexion
if (isset($_POST["logout"])) {
    // Déconnexion de l'utilisateur
    session_unset();     // Suppression des variables de session
    session_destroy();   // Destruction de la session
    header("Location: login.php"); // Redirection vers la page de connexion
    exit();
}

// Fonction pour ajouter un utilisateur
function ajouterUtilisateur($nom, $email, $role, $mot_de_passe) {
    global $conn;
    $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO Utilisateurs (nom, email, role, mot_de_passe_hashé) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nom, $email, $role, $mot_de_passe_hash);
    $stmt->execute();
    $stmt->close();
}

// Fonction pour modifier un utilisateur
function modifierUtilisateur($id_utilisateur, $nom, $email, $role, $mot_de_passe = null) {
    global $conn;
    if ($mot_de_passe) {
        $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE Utilisateurs SET nom = ?, email = ?, role = ?, mot_de_passe_hashé = ? WHERE id_utilisateur = ?");
        $stmt->bind_param("ssssi", $nom, $email, $role, $mot_de_passe_hash, $id_utilisateur);
    } else {
        $stmt = $conn->prepare("UPDATE Utilisateurs SET nom = ?, email = ?, role = ? WHERE id_utilisateur = ?");
        $stmt->bind_param("sssi", $nom, $email, $role, $id_utilisateur);
    }
    $stmt->execute();
    $stmt->close();
}

// Fonction pour supprimer un utilisateur
function supprimerUtilisateur($id_utilisateur) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM Utilisateurs WHERE id_utilisateur = ?");
    $sql_delete_rdv = "DELETE FROM Rendez_vous WHERE id_utilisateur = '$id_utilisateur'";
    $conn->query($sql_delete_rdv);
    $stmt->bind_param("i", $id_utilisateur);
    $stmt->execute();
    $stmt->close();
}

// Fonction pour ajouter une prestation
function ajouterPrestation($nom_prestation, $description, $tarif) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO Prestations (nom_prestation, description, tarif) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $nom_prestation, $description, $tarif);
    $stmt->execute();
    $stmt->close();
}

// Fonction pour modifier une prestation
function modifierPrestation($id_prestation, $nom_prestation, $description, $tarif) {
    global $conn;
    $stmt = $conn->prepare("UPDATE Prestations SET nom_prestation = ?, description = ?, tarif = ? WHERE id_prestation = ?");
    $stmt->bind_param("ssdi", $nom_prestation, $description, $tarif, $id_prestation);
    $stmt->execute();
    $stmt->close();
}

// Fonction pour supprimer une prestation
function supprimerPrestation($id_prestation) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM Prestations WHERE id_prestation = ?");
    $stmt->bind_param("i", $id_prestation);
    $stmt->execute();
    $stmt->close();
}

// Récupérer tous les utilisateurs
function getUtilisateurs() {
    global $conn;
    $utilisateurs = array();
    $result = $conn->query("SELECT * FROM Utilisateurs");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $utilisateurs[] = $row;
        }
    }
    return $utilisateurs;
}

// Récupérer toutes les prestations
function getPrestations() {
    global $conn;
    $prestations = array();
    $result = $conn->query("SELECT * FROM Prestations");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $prestations[] = $row;
        }
    }
    return $prestations;
}

// Traitement des actions des formulaires
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST["action"] == "user") {
        // Traitement des actions liées aux utilisateurs
        if (isset($_POST["ajouter"])) {
            // Ajout d'un nouvel utilisateur
            if (isset($_POST["nom"]) && isset($_POST["email"]) && isset($_POST["role"]) && isset($_POST["mot_de_passe"])) {
                ajouterUtilisateur($_POST["nom"], $_POST["email"], $_POST["role"], $_POST["mot_de_passe"]);
            }
        } elseif (isset($_POST["modifier"])) {
            // Modification d'un utilisateur existant
            if (isset($_POST["id_utilisateur"]) && isset($_POST["nom"]) && isset($_POST["email"]) && isset($_POST["role"])) {
                $mot_de_passe = isset($_POST["mot_de_passe"]) && !empty($_POST["mot_de_passe"]) ? $_POST["mot_de_passe"] : null;
                modifierUtilisateur($_POST["id_utilisateur"], $_POST["nom"], $_POST["email"], $_POST["role"], $mot_de_passe);
            }
        } elseif (isset($_POST["supprimer"])) {
            // Suppression d'un utilisateur
            supprimerUtilisateur($_POST["id_utilisateur"]);
        }
    } elseif ($_POST["action"] == "prestation") {
        // Traitement des actions liées aux prestations
        if (isset($_POST["ajouter"])) {
            // Ajout d'une nouvelle prestation
            if (isset($_POST["nom_prestation"]) && isset($_POST["description"]) && isset($_POST["tarif"])) {
                ajouterPrestation($_POST["nom_prestation"], $_POST["description"], $_POST["tarif"]);
            }
        } elseif (isset($_POST["modifier"])) {
            // Modification d'une prestation existante
            if (isset($_POST["id_prestation"]) && isset($_POST["nom_prestation"]) && isset($_POST["description"]) && isset($_POST["tarif"])) {
                modifierPrestation($_POST["id_prestation"], $_POST["nom_prestation"], $_POST["description"], $_POST["tarif"]);
            }
        } elseif (isset($_POST["supprimer"])) {
            // Suppression d'une prestation
            supprimerPrestation($_POST["id_prestation"]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Gérant</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
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
            padding: 15px;
            width: 100%;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
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
        .btn-primary {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Panel Gérant</h1>
        <div class="navbar-right">
            <form method="post" action="">
            	<a class="btn-black" href="gerant.php">Accueil Panel</a>
                <button type="submit" class="btn btn-danger" name="logout">Déconnexion</button>
            </form>
        </div>
    </div>
    <div class="container mt-5">
        <h2>Gestion des Utilisateurs</h2>
        <form method="post" action="">
            <input type="hidden" name="action" value="user">
            <input type="hidden" id="id_utilisateur" name="id_utilisateur">
            <div class="form-group">
                <label for="nom">Nom :</label>
                <input type="text" class="form-control" id="nom" name="nom" required>
            </div>
            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="role">Rôle :</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="client">Client</option>
                    <option value="gerant">Gérant</option>
                    <option value="coiffeur">Coiffeur</option>
                    <option value="comptable">Comptable</option>
                </select>
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe :</label>
                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe">
            </div>
            <button type="submit" class="btn btn-primary" name="ajouter">Ajouter Utilisateur</button>
            <button type="submit" class="btn btn-secondary" name="modifier">Modifier Utilisateur</button>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $utilisateurs = getUtilisateurs();
                foreach ($utilisateurs as $utilisateur) {
                    echo "<tr>";
                    echo "<td>" . $utilisateur['nom'] . "</td>";
                    echo "<td>" . $utilisateur['email'] . "</td>";
                    echo "<td>" . $utilisateur['role'] . "</td>";
                    echo "<td>";
                    echo "<form method='post' action=''>";
                    echo "<input type='hidden' name='action' value='user'>";
                    echo "<input type='hidden' name='id_utilisateur' value='" . $utilisateur['id_utilisateur'] . "'>";
                    echo "<button type='button' class='btn btn-info' onclick='remplirFormulaireUtilisateur(" . $utilisateur['id_utilisateur'] . ", \"" . $utilisateur['nom'] . "\", \"" . $utilisateur['email'] . "\", \"" . $utilisateur['role'] . "\")'>Sélectionner</button>";
                    echo "<button type='submit' class='btn btn-danger' name='supprimer'>Supprimer</button>";
                    echo "</form>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="container mt-5">
        <h2>Gestion des Prestations</h2>
        <form method="post" action="">
            <input type="hidden" name="action" value="prestation">
            <input type="hidden" id="id_prestation" name="id_prestation">
            <div class="form-group">
                <label for="nom_prestation">Nom de la prestation :</label>
                <input type="text" class="form-control" id="nom_prestation" name="nom_prestation" required>
            </div>
            <div class="form-group">
                <label for="description">Description :</label>
                <textarea class="form-control" id="description" name="description" required></textarea>
            </div>
            <div class="form-group">
                <label for="tarif">Tarif :</label>
                <input type="number" class="form-control" id="tarif" name="tarif" step="0.01" required>
            </div>
            <button type="submit" class="btn btn-primary" name="ajouter">Ajouter Prestation</button>
            <button type="submit" class="btn btn-secondary" name="modifier">Modifier Prestation</button>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Tarif</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $prestations = getPrestations();
                foreach ($prestations as $prestation) {
                    echo "<tr>";
                    echo "<td>" . $prestation['nom_prestation'] . "</td>";
                    echo "<td>" . $prestation['description'] . "</td>";
                    echo "<td>" . $prestation['tarif'] . "</td>";
                    echo "<td>";
                    echo "<form method='post' action=''>";
                    echo "<input type='hidden' name='action' value='prestation'>";
                    echo "<input type='hidden' name='id_prestation' value='" . $prestation['id_prestation'] . "'>";
                    echo "<button type='button' class='btn btn-info' onclick='remplirFormulairePrestation(" . $prestation['id_prestation'] . ", \"" . $prestation['nom_prestation'] . "\", \"" . $prestation['description'] . "\", " . $prestation['tarif'] . ")'>Sélectionner</button>";
                    echo "<button type='submit' class='btn btn-danger' name='supprimer'>Supprimer</button>";
                    echo "</form>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        function remplirFormulaireUtilisateur(id_utilisateur, nom, email, role) {
            document.getElementById("id_utilisateur").value = id_utilisateur;
            document.getElementById("nom").value = nom;
            document.getElementById("email").value = email;
            document.getElementById("role").value = role;
            document.getElementById("mot_de_passe").value = '';
        }

        function remplirFormulairePrestation(id_prestation, nom_prestation, description, tarif) {
            document.getElementById("id_prestation").value = id_prestation;
            document.getElementById("nom_prestation").value = nom_prestation;
            document.getElementById("description").value = description;
            document.getElementById("tarif").value = tarif;
        }
    </script>
    <script>
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 120000);
    </script>
</body>
</html>

