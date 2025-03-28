<?php
session_start();
require('db.php');

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit();
}

// Gestion de la soumission du formulaire
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['mail']);
    $motPasse = trim($_POST['motPasse']);

    // Validation des champs
    if (empty($nom) || empty($prenom) || empty($email) || empty($motPasse)) {
        $message = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "L'email n'est pas valide.";
    } elseif (strlen($motPasse) < 8) {
        $message = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        // Vérifier si l'email existe déjà
        $sql = "SELECT idUser FROM utilisateur WHERE mailUser = :email";
        $stm = $pdo->prepare($sql);
        $stm->execute([':email' => $email]);
        if ($stm->fetch()) {
            $message = "Cet email est déjà utilisé.";
        } else {
            // Générer un idUser unique
            $idUser = 'U' . substr(uniqid(), -9);

            // Insérer l'utilisateur dans la base (mot de passe non haché)
            $sql = "INSERT INTO utilisateur (idUser, nomUser, prenomUser, mailUser, motPasse) 
                    VALUES (:idUser, :nom, :prenom, :email, :motPasse)";
            $stm = $pdo->prepare($sql);
            $stm->execute([
                ':idUser' => $idUser,
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':motPasse' => $motPasse
            ]);

            $message = "Inscription réussie ! <a href='login.php'>Connectez-vous ici</a>.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - FarhaEvents</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fdfaf6;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 500px;
            margin: 40px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #e67e22;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #e67e22;
            border-radius: 6px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            border-color: #c0392b;
            outline: none;
        }

        button {
            background-color: #e67e22;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #c0392b;
        }

        .message {
            margin: 15px 0;
            padding: 12px;
            border-radius: 6px;
            text-align: center;
            font-weight: bold;
        }

        .message.success {
            background-color: #eafaf1;
            color: #2ecc71;
            border: 1px solid #2ecc71;
        }

        .message.error {
            background-color: #fdecea;
            color: #c0392b;
            border: 1px solid #c0392b;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 15px;
        }

        .login-link a {
            color: #e67e22;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            color: #c0392b;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Inscription</h1>
        <?php if ($message) : ?>
            <div class="message <?= strpos($message, 'réussie') !== false ? 'success' : 'error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="nom">Nom :</label>
                <input type="text" id="nom" name="nom" value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label for="prenom">Prénom :</label>
                <input type="text" id="prenom" name="prenom" value="<?= isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label for="mail">Email :</label>
                <input type="email" id="mail" name="mail" value="<?= isset($_POST['mail']) ? htmlspecialchars($_POST['mail']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label for="motPasse">Mot de passe :</label>
                <input type="password" id="motPasse" name="motPasse" required>
            </div>
            <button type="submit">S'inscrire</button>
        </form>
        <div class="login-link">
            <p>Déjà un compte ? <a href="login.php">Connectez-vous</a></p>
        </div>
    </div>
</body>
</html>