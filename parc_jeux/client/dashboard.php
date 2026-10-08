<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/models.php';
checkRole(['client']);
$id = $_SESSION['user']['id'];

// Contrôleur : récupération des données via les modèles
$reservations = new ReservationModel($pdo);
$nbRes = $reservations->countActives($id);
$nbBillets = (new CommandeModel($pdo))->countBillets($id);
$prochaine = $reservations->prochaineVisite($id);

$pageTitle = 'Mon espace – Parc 9ABOUYAWA';
$appShell = true; $activeNav = 'dashboard'; $pageHeading = 'Tableau de bord';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div><h1>Bonjour <?= e($_SESSION['user']['prenom']) ?> 👋</h1><p>Voici un aperçu de votre activité au parc.</p></div>
  <a class="btn btn-amber" href="reserver.php"><i class="fa-solid fa-calendar-plus"></i> Nouvelle réservation</a>
</div>
<?php showFlash(); ?>

<div class="stats-row">
  <div class="stat-card"><span class="stat-ico"><i class="fa-solid fa-calendar-check"></i></span><div><strong><?= $nbRes ?></strong><span>Réservations actives</span></div></div>
  <div class="stat-card"><span class="stat-ico amber"><i class="fa-solid fa-ticket"></i></span><div><strong><?= $nbBillets ?></strong><span>Billets achetés</span></div></div>
  <div class="stat-card"><span class="stat-ico green"><i class="fa-solid fa-sun"></i></span><div><strong><?= $prochaine ? e(dateFr($prochaine['date_visite'], false)) : '—' ?></strong><span>Prochaine visite</span></div></div>
</div>

<div class="dash-grid2">
  <?php if ($prochaine): ?>
  <section class="panel next-visit">
    <span class="eyebrow">Prochaine visite · <?= e(statutResLabel($prochaine['statut'])) ?></span>
    <div class="next-date"><?= e(dateFr($prochaine['date_visite'])) ?></div>
    <ul class="next-list">
      <?php foreach ($prochaine['lignes'] as $l): ?>
        <li><time><?= e(date('H:i', strtotime($l['creneau']))) ?></time><span><?= e($l['nom']) ?></span></li>
      <?php endforeach; ?>
    </ul>
    <a class="btn btn-amber" href="reservations.php">Gérer mes réservations</a>
  </section>
  <?php else: ?>
  <div class="empty-state">
    <span class="empty-icon"><i class="fa-regular fa-calendar"></i></span>
    <h2>Aucune visite à venir</h2>
    <p>Composez votre journée : choisissez vos jeux et vos horaires, on s'occupe du reste.</p>
    <a class="btn btn-amber" href="reserver.php">Planifier ma journée</a>
  </div>
  <?php endif; ?>

  <section class="panel">
    <h3>Accès rapide</h3>
    <div class="quick-actions">
      <a href="reserver.php"><i class="fa-solid fa-calendar-plus"></i> Réserver des jeux <i class="fa-solid fa-chevron-right"></i></a>
      <a href="reservations.php"><i class="fa-solid fa-calendar-check"></i> Mes réservations <i class="fa-solid fa-chevron-right"></i></a>
      <a href="acheter.php"><i class="fa-solid fa-ticket"></i> Acheter des billets <i class="fa-solid fa-chevron-right"></i></a>
      <a href="achats.php"><i class="fa-solid fa-receipt"></i> Mes achats <i class="fa-solid fa-chevron-right"></i></a>
    </div>
  </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
