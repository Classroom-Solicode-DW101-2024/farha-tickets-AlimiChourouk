<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require('db.php');

function getUniqueDates($pdo) {  
    $sql = "SELECT  dateEvent FROM edition ORDER BY dateEvent ASC";
    $stm = $pdo->query($sql);
    return $stm->fetchAll(PDO::FETCH_COLUMN);
}

function getAllEvenements($pdo) {
    $sql = "SELECT 
        ed.editionId,
        ed.dateEvent,
        ed.timeEvent,
        ed.NumSalle,
        ed.image,
        ev.eventId,
        ev.eventType,
        ev.eventTitle,
        ev.eventDescription,
        ev.TariffNormal,
        ev.TariffReduit,
        s.capSalle
    FROM edition ed
    INNER JOIN evenement ev ON ed.eventId = ev.eventId
    INNER JOIN salle s ON ed.NumSalle = s.NumSalle";
    $stm = $pdo->query($sql);
    return $stm->fetchAll(PDO::FETCH_ASSOC);
}

function getFilteredEvenements($pdo, $dateDebut = null, $dateFin = null, $categorie = null, $searchTitle = null) {
    $sql = "SELECT 
        ed.editionId,
        ed.dateEvent,
        ed.timeEvent,
        ed.NumSalle,
        ed.image,
        ev.eventId,
        ev.eventType,
        ev.eventTitle,
        ev.eventDescription,
        ev.TariffNormal,
        ev.TariffReduit,
        s.capSalle
    FROM edition ed
    INNER JOIN evenement ev ON ed.eventId = ev.eventId
    INNER JOIN salle s ON ed.NumSalle = s.NumSalle
    WHERE 1=1";
    $params = [];
    if ($dateDebut && $dateFin && $dateDebut !== 'all' && $dateFin !== 'all') {
        $sql .= " AND ed.dateEvent BETWEEN :dateDebut AND :dateFin";
        $params[':dateDebut'] = $dateDebut;
        $params[':dateFin'] = $dateFin;
    }
    if ($categorie && $categorie !== 'all') {
        $sql .= " AND ev.eventType = :categorie";
        $params[':categorie'] = $categorie;
    }
    if ($searchTitle) {
        $sql .= " AND ev.eventTitle LIKE :searchTitle";
        $params[':searchTitle'] = "%" . $searchTitle . "%"; // Recherche partielle avec LIKE
    }
    $stm = $pdo->prepare($sql);
    $stm->execute($params);
    return $stm->fetchAll(PDO::FETCH_ASSOC);
}

try {
    $dates = getUniqueDates($pdo);
    $categories = ['all' => 'Toutes', 'Musique' => 'Musique', 'Théâtre' => 'Théâtre', 'Cinéma' => 'Cinéma', 'Rencontres' => 'Rencontres'];
    $dateDebut = isset($_GET['dateDebut']) ? $_GET['dateDebut'] : null;
    $dateFin = isset($_GET['dateFin']) ? $_GET['dateFin'] : null;
    $categorie = isset($_GET['categorie']) ? $_GET['categorie'] : null;
    $searchTitle = isset($_GET['searchTitle']) ? $_GET['searchTitle'] : null;

    if ($dateDebut || $dateFin || $categorie || $searchTitle) {
        $evenements = getFilteredEvenements($pdo, $dateDebut, $dateFin, $categorie, $searchTitle);
    } else {
        $evenements = getAllEvenements($pdo);
    }
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <title>Liste des Événements</title>
     
</head>
<body>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./style.css?v=<?= time() ?>">
    <title>Liste des Événements</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<header>
    <div class="header-container">
        <div class="logo">
            <img src="img/logo.png" alt="Logo">
        </div>
        <nav>
            <a href="index.php">Accueil</a>
            <a href="#events-grid">Événements</a>
            <?php
    if (isset($_SESSION['userId'])) {
        // Vérifier si l'utilisateur a des réservations
        $sql = "SELECT COUNT(*) FROM reservation WHERE idUser = :userId";
        $stm = $pdo->prepare($sql);
        $stm->execute([':userId' => $_SESSION['userId']]);
        $hasReservations = $stm->fetchColumn() > 0;

        if ($hasReservations) {
            echo '<a href="historique.php">Vos réservations</a>';
        }
        echo '<p id="user">Bienvenue, ' . htmlspecialchars($_SESSION['user_prenom']) . ' | <a href="deconnexion.php">Déconnexion</a></p>';
    } else {
        echo '<a href="login.php">Connexion</a> | <a href="register.php">Inscription</a>';
    }
    ?>
        </nav>
    </div>
</header>


    <div class="container">
        <h1>Liste des Événements</h1>
        
        <div class="filters">
            <form method="GET" action="">
                <label for="searchTitle">Rechercher par titre :</label>
                <input type="text" id="searchTitle" name="searchTitle"
                       value="<?= htmlspecialchars(isset($_GET['searchTitle']) ? $_GET['searchTitle'] : '') ?>"
                       placeholder="Entrez un titre...">

                <label for="dateDebut">Date début :</label>
                <select id="dateDebut" name="dateDebut">
                    <option value="all" <?= $dateDebut === 'all' || !$dateDebut ? 'selected' : '' ?>>Toutes</option>
                    <?php foreach ($dates as $date) : ?>
                        <option value="<?= $date ?>" <?= $dateDebut === $date ? 'selected' : '' ?>><?= $date ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="dateFin">Date fin :</label>
                <select id="dateFin" name="dateFin">
                    <option value="all" <?= $dateFin === 'all' || !$dateFin ? 'selected' : '' ?>>Toutes</option>
                    <?php foreach ($dates as $date) : ?>
                        <option value="<?= $date ?>" <?= $dateFin === $date ? 'selected' : '' ?>><?= $date ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="categorie">Catégorie :</label>
                <select id="categorie" name="categorie">
                    <?php foreach ($categories as $value => $label) : ?>
                        <option value="<?= $value ?>" <?= $categorie === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" value="Filtrer">
            </form>
        </div>

        <?php if (empty($evenements)) : ?>
            <div class="no-events">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                </svg>
                <p>Aucun événement trouvé.</p>
            </div>
        <?php else : ?>
            <div class="events-grid" id="events-grid">
                <?php foreach ($evenements as $event) : ?>
                    <div class="event">
                        <h2><?= htmlspecialchars($event['eventTitle']) ?></h2>
                        <img src="<?= htmlspecialchars($event['image']) ?>" alt="Image de l'événement">
                        <div class="event-date">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1z"/>
                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                            </svg>
                            <p><strong>Date :</strong> <?= htmlspecialchars($event['dateEvent']) ?></p>
                        </div>
                        <p><strong>Catégorie :</strong> <?= htmlspecialchars($event['eventType']) ?></p>
                        <form action="details.php" method="POST">
                            <button type="submit" name="editionId" value="<?= htmlspecialchars($event['editionId']) ?>">J'achète</button>
                        </form>
                        <hr>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <footer>
        <div class="footer-content">
            <p>&copy; <?= date('Y') ?> MonSite - Tous droits réservés</p>
           </div>
    </footer>
</body>
</html>