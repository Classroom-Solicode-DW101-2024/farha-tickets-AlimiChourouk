<?php
session_start();
require('db.php');

if (isset($_SESSION['userId'])) {
    header("Location: index.php");
    exit();
}

$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
$_SESSION['redirect_url'] = $redirect_url;

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['mail']);
    $motPasse = trim($_POST['motPasse']);

    if (empty($email) || empty($motPasse)) {
        $message = "Tous les champs sont obligatoires.";
    } else {
        $sql = "SELECT idUser, prenomUser, nomUser, motPasse FROM utilisateur WHERE mailUser = :email";
        $stm = $pdo->prepare($sql);
        $stm->execute([':email' => $email]);
        $user = $stm->fetch(PDO::FETCH_ASSOC);

        echo '<pre>';
        var_dump($user);
        echo '</pre>';

        if ($user) {
            if ($motPasse === $user['motPasse']) {
                $_SESSION['userId'] = $user['idUser'];
                $_SESSION['user_prenom'] = $user['prenomUser'];
                $_SESSION['user_nom'] = $user['nomUser'];
                // Débogage : Afficher la session avant redirection
                echo '<pre>';
                var_dump($_SESSION);
                echo '</pre>';
                $destination = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'index.php';
                unset($_SESSION['redirect_url']);
                header("Location: $destination");
                exit();
            } else {
                $message = "Email ou mot de passe incorrect.";
            }
        } else {
            $message = "Email ou mot de passe incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - FarhaEvents</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #fdfaf6;
        margin: 0;
        padding: 20px;
    }

    .container {
        max-width: 500px;
        margin: 0 auto;
        background: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    h1 {
        color: #e67e22;
        text-align: center;
        margin-bottom: 25px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #333;
    }

    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #e67e22;
        border-radius: 5px;
        box-sizing: border-box;
        font-size: 14px;
        transition: border-color 0.3s ease;
    }

    .form-group input:focus {
        border-color: #c0392b;
        outline: none;
    }

    button {
        background-color: #e67e22;
        color: #fff;
        border: none;
        padding: 12px;
        border-radius: 6px;
        cursor: pointer;
        width: 100%;
        font-size: 16px;
        transition: background-color 0.3s ease;
    }

    button:hover {
        background-color: #c0392b;
    }

    .message {
        margin: 20px 0;
        padding: 12px;
        border-radius: 5px;
        text-align: center;
        background-color: #fceae9;
        color: #c0392b;
        border: 1px solid #c0392b;
    }

    .register-link {
        text-align: center;
        margin-top: 20px;
    }

    .register-link a {
        color: #e67e22;
        text-decoration: none;
        font-weight: bold;
    }

    .register-link a:hover {
        color: #c0392b;
        text-decoration: underline;
    }
</style>

</head>
<body>
<div class="container">
    <h1>Connexion</h1>
    <?php if ($message) : ?>
        <div class="message">
            <?= $message ?>
        </div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="form-group">
            <label for="mail">Email :</label>
            <input type="email" id="mail" name="mail" value="<?= isset($_POST['mail']) ? htmlspecialchars($_POST['mail']) : '' ?>" required>
        </div>
        <div class="form-group">
            <label for="motPasse">Mot de passe :</label>
            <input type="password" id="motPasse" name="motPasse" required>
        </div>
        <button type="submit">Se connecter</button>
    </form>
    <div class="register-link">
        <p>Pas de compte ? <a href="register.php">Inscrivez-vous</a></p>
    </div>
</div>
</body>
</html>