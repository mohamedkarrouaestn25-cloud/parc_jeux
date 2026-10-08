<?php
if (session_status() === PHP_SESSION_NONE) {
    // Cookie de session non lisible en JavaScript, non envoyé depuis un site tiers
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
require_once __DIR__ . '/functions.php';

define('BASE_URL', '/parc_jeux');

function isLoggedIn(): bool {
    return isset($_SESSION['user']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
        exit;
    }
}

// Exemple : checkRole(['admin']) ou checkRole(['admin', 'responsable'])
function checkRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['user']['role'], $roles, true)) {
        abortPage(403, 'Accès refusé', "Vous n'avez pas le droit d'accéder à cette page.");
    }
}

function redirectByRole(string $role): void {
    header('Location: ' . BASE_URL . '/' . $role . '/dashboard.php');
    exit;
}

// N'accepte que les adresses internes au site (évite les redirections vers un autre site)
function safeNext(?string $next): ?string {
    if ($next && strpos($next, BASE_URL . '/') === 0 && strpos($next, '//') === false && strpos($next, "\n") === false) {
        return $next;
    }
    return null;
}
