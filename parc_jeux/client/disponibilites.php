<?php
// Disponibilités d'un jour (lecture seule) : places déjà prises par jeu et par créneau.
// Utilisé par le planificateur de réservation ; la capacité reste de toute façon revérifiée à la validation.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/models.php';

header('Content-Type: application/json; charset=utf-8');
if (!isLoggedIn() || $_SESSION['user']['role'] !== 'client') {
    http_response_code(401);
    echo json_encode(['error' => 'Authentification requise.']);
    exit;
}
$date = $_GET['date'] ?? '';
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    http_response_code(400);
    echo json_encode(['error' => 'Date invalide.']);
    exit;
}
echo json_encode(['date' => $date, 'pris' => (new ReservationModel($pdo))->placesPrises($date)], JSON_FORCE_OBJECT);
