<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
checkRole(['agent']);

$pageTitle = 'Espace agent – Parc 9ABOUYAWA';
$appShell = true; $activeNav = 'dashboard'; $pageHeading = 'Tableau de bord';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><div><h1>Bonjour <?= e($_SESSION['user']['prenom']) ?></h1><p>Espace <?= e(strtolower(roleLabel('agent'))) ?>.</p></div></div>
<?php showFlash(); ?>
<div class="empty-state">
  <span class="empty-icon"><i class="fa-solid fa-screwdriver-wrench"></i></span>
  <h2>Espace en cours de construction</h2>
  <p>Les fonctionnalités de ce rôle seront ajoutées ici.</p>
  <a class="btn btn-pine" href="<?= BASE_URL ?>/index.php">Voir le site public</a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
