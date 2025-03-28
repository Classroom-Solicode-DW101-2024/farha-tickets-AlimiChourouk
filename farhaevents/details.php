<?php
session_start();
require('db.php');

// Vérifier si editionId est fourni via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['normalTickets'])) {
    if (!isset($_POST['editionId']) || !is_numeric($_POST['editionId'])) {
        die("Erreur : ID d'édition non valide ou manquant dans la requête POST initiale.");
    }
    $editionId = (int)$_POST['editionId'];
} elseif (isset($_POST['editionIdHidden']) && is_numeric($_POST['editionIdHidden'])) {
    $editionId = (int)$_POST['editionIdHidden'];
} else {
    die("Erreur : ID d'édition manquant ou invalide.");
}

// Récupération des détails de l'événement
$sql = "SELECT 
    ed.editionId, ed.dateEvent, ed.timeEvent, ed.NumSalle, ed.image,
    ev.eventId, ev.eventType, ev.eventTitle, ev.eventDescription, 
    ev.TariffNormal, ev.TariffReduit,
    s.capSalle
FROM edition ed
INNER JOIN evenement ev ON ed.eventId = ev.eventId
INNER JOIN salle s ON ed.NumSalle = s.NumSalle
WHERE ed.editionId = :editionId";
$stm = $pdo->prepare($sql);
$stm->execute([':editionId' => $editionId]);
$event = $stm->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Erreur : Événement non trouvé pour l'ID $editionId.");
}

// Vérification des billets restants
$sql = "SELECT SUM(qteBilletsNormal + qteBilletsReduit) as totalTickets 
        FROM reservation 
        WHERE editionId = :editionId";
$stm = $pdo->prepare($sql);
$stm->execute([':editionId' => $editionId]);
$totalTicketsSold = $stm->fetch(PDO::FETCH_ASSOC)['totalTickets'] ?? 0;
$remainingCapacity = $event['capSalle'] - $totalTicketsSold;

// Traitement du formulaire d'achat
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['normalTickets'])) {
    if (!isset($_SESSION['userId'])) {
        $message = "Vous devez être connecté pour valider votre achat. <a href='login.php'>Connectez-vous ici</a>.";
    } else {
        $normalTickets = (int)$_POST['normalTickets'];
        $reducedTickets = (int)$_POST['reducedTickets'];
        $totalRequested = $normalTickets + $reducedTickets;

        if ($totalRequested > 0 && $totalRequested <= $remainingCapacity) {
            // Insertion réservation
            $sql = "INSERT INTO reservation (qteBilletsNormal, qteBilletsReduit, editionId, idUser) 
                    VALUES (:normal, :reduced, :editionId, :userId)";
            $stm = $pdo->prepare($sql);
            $stm->execute([
                ':normal' => $normalTickets,
                ':reduced' => $reducedTickets,
                ':editionId' => $editionId,
                ':userId' => $_SESSION['userId']
            ]);
            $reservationId = $pdo->lastInsertId();

            // Génération billets
            $lastSeat = $totalTicketsSold;
            for ($i = 1; $i <= $totalRequested; $i++) {
                $seatNum = $lastSeat + $i;
                $billetId = "BLT" . sprintf("%08d", rand(1, 99999999));
                $type = ($i <= $normalTickets) ? 'Normal' : 'Réduit';

                $sql = "INSERT INTO billet (billetId, typeBillet, placeNum, idReservation) 
                        VALUES (:billetId, :type, :placeNum, :reservationId)";
                $stm = $pdo->prepare($sql);
                $stm->execute([
                    ':billetId' => $billetId,
                    ':type' => $type,
                    ':placeNum' => $seatNum,
                    ':reservationId' => $reservationId
                ]);
            }

            $message = "Réservation confirmée ! ";
        } else {
            $message = "Erreur : Nombre de billets demandé non disponible.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="styles.css">
    <title>Détails de l'événement - <?= htmlspecialchars($event['eventTitle']) ?></title>
    
</head>
<body>
    
<div class="container">
    <h1><?= htmlspecialchars($event['eventTitle']) ?></h1>
    <img src="<?= htmlspecialchars($event['image']) ?>" alt="Image de l'événement" class="event-image">

    <div class="info-box">
        <p><strong>Date :</strong> <?= htmlspecialchars($event['dateEvent']) ?> à <?= htmlspecialchars($event['timeEvent']) ?></p>
        <p><strong>Type :</strong> <?= htmlspecialchars($event['eventType']) ?></p>
    </div>

    <div class="details">
        <p><strong>Description :</strong> <?= htmlspecialchars($event['eventDescription']) ?></p>
        <p><strong>Tarif Normal :</strong> <?= htmlspecialchars($event['TariffNormal']) ?>€</p>
        <p><strong>Tarif Réduit :</strong> <?= htmlspecialchars($event['TariffReduit']) ?>€</p>
    </div>

    <hr>

    <?php if ($remainingCapacity > 0) : ?>
        <h2>Réserver vos billets</h2>
        <form method="POST" action="">
            <input type="hidden" name="editionIdHidden" value="<?= htmlspecialchars($editionId) ?>">

            <div class="form-group">
                <label for="normalTickets">Billets Normaux :</label>
                <input type="number" id="normalTickets" name="normalTickets" min="0" max="<?= $remainingCapacity ?>" value="0">
            </div>
            <div class="form-group">
                <label for="reducedTickets">Billets Réduits :</label>
                <input type="number" id="reducedTickets" name="reducedTickets" min="0" max="<?= $remainingCapacity ?>" value="0">
            </div>

            <?php if (isset($_SESSION['userId'])) : ?>
                <button type="submit">Valider l'achat</button>
            <?php else : ?>
                <p class="message error">Veuillez vous connecter pour valider votre achat.</p>
                <a href="login.php" class="btn-login">Se connecter</a>
            <?php endif; ?>
        </form>

        <?php if ($message) : ?>
            <div class="message <?= strpos($message, 'Erreur') !== false || strpos($message, 'connecté') !== false ? 'error' : 'success' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <p class="message error">Guichet fermé - Plus de places disponibles.</p>
    <?php endif; ?>

    <div class="navigation">
        <a href="index.php" class="back-link">← Retour à la liste des événements</a>
    </div>
</div>

</body>
</html>