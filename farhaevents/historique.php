<?php
session_start();
require('db.php');


if (!isset($_SESSION['userId'])) {
    header("Location: connexion.php");
    exit();
}


$userId = $_SESSION['userId'];

$sql = "SELECT 
    r.idReservation, 
    r.qteBilletsNormal, 
    r.qteBilletsReduit, 
    ed.dateEvent, 
    ed.timeEvent, 
    ev.eventTitle, 
    ev.eventType, 
    ev.TariffNormal, 
    ev.TariffReduit, 
    s.NumSalle
FROM reservation r
INNER JOIN edition ed ON r.editionId = ed.editionId
INNER JOIN evenement ev ON ed.eventId = ev.eventId
INNER JOIN salle s ON ed.NumSalle = s.NumSalle
WHERE r.idUser = :userId
ORDER BY ed.dateEvent DESC";

try {
    $stm = $pdo->prepare($sql);
    $stm->execute([':userId' => $userId]);
    $reservations = $stm->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des réservations : " . $e->getMessage());
}

$hasReservations = !empty($reservations);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vos Réservations - FarhaEvents</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./style.css?v=<?= time() ?>"> <!-- Assurez-vous que style.css existe -->
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #fdfaf6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
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
        nav {
            text-align: center;
            margin-bottom: 20px;
        }
        nav a {
            margin: 0 15px;
            text-decoration: none;
            color: #e67e22;
            font-weight: 500;
        }
        nav a:hover {
            color: #c0392b;
            text-decoration: underline;
        }
        nav p#user {
            display: inline;
            margin: 0 15px;
            color: #333;
        }
        .reservation {
            border-bottom: 1px solid #e67e22;
            padding: 15px 0;
        }
        .reservation:last-child {
            border-bottom: none;
        }
        .reservation p {
            margin: 5px 0;
            color: #333;
        }
        .reservation p strong {
            color: #e67e22;
        }
        .no-reservations {
            text-align: center;
            color: #c0392b;
            font-size: 18px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav>
            <a href="index.php">Accueil</a>
            <a href="index.php#events-grid">Événements</a>
            <?php if (isset($_SESSION['userId'])): ?>
                <?php if ($hasReservations): ?>
                    <a href="historique.php">Vos réservations</a>
                <?php endif; ?>
                <p id="user">Bienvenue, <?= htmlspecialchars($_SESSION['user_prenom'] ?? 'Utilisateur') ?> | <a href="deconnexion.php">Déconnexion</a></p>
            <?php else: ?>
                <a href="connexion.php">Connexion</a> | <a href="inscription.php">Inscription</a>
            <?php endif; ?>
        </nav>

        <h1>Vos Réservations</h1>

        <?php if (empty($reservations)): ?>
            <p class="no-reservations">Vous n'avez aucune réservation pour le moment.</p>
        <?php else: ?>
            <div class="reservations-list">
                <?php foreach ($reservations as $reservation): ?>
                    <div class="reservation">
                        <p><strong>Événement :</strong> <?= htmlspecialchars($reservation['eventTitle']) ?></p>
                        <p><strong>Type :</strong> <?= htmlspecialchars($reservation['eventType']) ?></p>
                        <p><strong>Date :</strong> <?= htmlspecialchars($reservation['dateEvent']) ?> à <?= htmlspecialchars($reservation['timeEvent']) ?></p>
                        <p><strong>Salle :</strong> <?= htmlspecialchars($reservation['NumSalle']) ?></p>
                        <p><strong>Billets Normaux :</strong> <?= htmlspecialchars($reservation['qteBilletsNormal']) ?> (<?= htmlspecialchars($reservation['TariffNormal']) ?>€ chacun)</p>
                        <p><strong>Billets Réduits :</strong> <?= htmlspecialchars($reservation['qteBilletsReduit']) ?> (<?= htmlspecialchars($reservation['TariffReduit']) ?>€ chacun)</p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>