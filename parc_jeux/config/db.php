<?php
// Connexion PDO : modifier selon votre XAMPP
$host = 'localhost';
$dbname = 'parc_jeux';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données. Vérifiez que MySQL est démarré et que la base 'parc_jeux' existe.");
}
