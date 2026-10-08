<?php
function e($s): string {
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
}

function fmtPrix($n): string {
    return number_format((float)$n, 0, ',', ' ') . ' DH';
}

function etatLabel(string $etat): string {
    $m = [
        'disponible' => 'Disponible',
        'en_fonctionnement' => 'En fonctionnement',
        'en_maintenance' => 'En maintenance',
        'hors_service' => 'Hors service',
        'ferme_temporairement' => 'Fermé temporairement',
    ];
    return $m[$etat] ?? $etat;
}

function etatClass(string $etat): string {
    if ($etat === 'disponible' || $etat === 'en_fonctionnement') return '';
    return $etat === 'en_maintenance' ? 'warn' : 'bad';
}

function statutResLabel(string $s): string {
    $m = ['en_attente' => 'En attente', 'confirmee' => 'Confirmée', 'payee' => 'Payée',
          'utilisee' => 'Utilisée', 'annulee' => 'Annulée'];
    return $m[$s] ?? $s;
}

function statutResClass(string $s): string {
    if ($s === 'en_attente') return 'warn';
    return $s === 'annulee' ? 'bad' : '';
}

// Créneaux horaires d'une session (le parc ouvre de 10h à 20h)
function creneauxDispo(): array {
    $r = [];
    for ($h = 10; $h <= 19; $h++) $r[] = sprintf('%02d:00', $h);
    return $r;
}

function genCode(string $prefix, int $bytes = 3): string {
    return $prefix . '-' . strtoupper(bin2hex(random_bytes($bytes)));
}

function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function showFlash(): void {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert alert-' . e($f['type']) . '">' . e($f['msg']) . '</div>';
    }
}

/* ===== Sécurité : jeton CSRF pour tous les formulaires POST ===== */
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrfField(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}
function csrfCheck(): bool {
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''));
}
const CSRF_MSG = 'Session expirée ou formulaire invalide. Rechargez la page et réessayez.';

/* ===== Libellés et formats ===== */
function billetStatutLabel(string $s): string {
    $m = ['valide' => 'Valide', 'utilise' => 'Utilisé', 'expire' => 'Expiré', 'annule' => 'Annulé'];
    return $m[$s] ?? ucfirst($s);
}
function billetStatutClass(string $s): string {
    return $s === 'valide' ? '' : ($s === 'utilise' ? 'info' : 'warn');
}
function roleLabel(string $role): string {
    $m = ['admin' => 'Administrateur', 'responsable' => 'Responsable', 'agent' => 'Agent', 'client' => 'Visiteur'];
    return $m[$role] ?? ucfirst($role);
}
function initiales(string $prenom, string $nom): string {
    // Premier caractère (UTF-8) sans dépendre de l'extension mbstring
    $first = fn(string $s): string => preg_match('/./u', $s, $m) ? $m[0] : '';
    return strtoupper($first($prenom) . $first($nom));
}
// Date en français sans dépendre de l'extension intl : « mer. 7 oct. 2026 »
function dateFr(string $ymd, bool $withDay = true): string {
    $t = strtotime($ymd);
    $j = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
    $m = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    return ($withDay ? $j[(int)date('w', $t)] . ' ' : '') . (int)date('j', $t) . ' ' . $m[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
}

/* ===== Navigation de l'espace connecté (sidebar), selon le rôle ===== */
function navItems(string $role): array {
    $b = BASE_URL;
    $items = [['key' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => 'fa-gauge-high', 'href' => "$b/$role/dashboard.php"]];
    if ($role === 'client') {
        $items[] = ['key' => 'reserver', 'label' => 'Réserver', 'icon' => 'fa-calendar-plus', 'href' => "$b/client/reserver.php"];
        $items[] = ['key' => 'reservations', 'label' => 'Mes réservations', 'icon' => 'fa-calendar-check', 'href' => "$b/client/reservations.php"];
        $items[] = ['key' => 'acheter', 'label' => 'Acheter des billets', 'icon' => 'fa-ticket', 'href' => "$b/client/acheter.php"];
        $items[] = ['key' => 'achats', 'label' => 'Mes achats', 'icon' => 'fa-receipt', 'href' => "$b/client/achats.php"];
    }
    return $items;
}

/* ===== Page d'erreur stylée (403, 404…) au lieu d'un die() brut ===== */
function abortPage(int $code, string $title, string $message): void {
    http_response_code($code);
    $pageTitle = $title . ' – Parc 9ABOUYAWA';
    $appShell = isLoggedIn();
    $pageHeading = $title;
    require __DIR__ . '/header.php';
    echo '<div class="empty-state"><span class="empty-icon"><i class="fa-solid fa-' . ($code === 403 ? 'lock' : 'compass') . '"></i></span>'
       . '<h2>' . e($title) . '</h2><p>' . e($message) . '</p>'
       . '<a class="btn btn-pine" href="' . BASE_URL . '/index.php">Retour à l’accueil</a></div>';
    require __DIR__ . '/footer.php';
    exit;
}
