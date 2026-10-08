<?php
// Chargement de la couche Modèle. Usage dans un contrôleur : require_once __DIR__ . '/../includes/models.php';
foreach (['UserModel', 'JeuModel', 'ReservationModel', 'CommandeModel'] as $m) {
    require_once __DIR__ . '/../models/' . $m . '.php';
}
