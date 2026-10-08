<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/models.php';
checkRole(['client']);
$TICKETS = require __DIR__ . '/../config/tarifs.php';
$uid = $_SESSION['user']['id'];

$model = new CommandeModel($pdo);
$commandes = $model->forClient($uid);
$billets = $model->billetsPour(array_column($commandes, 'id_commande'));

$pageTitle = 'Mes achats – Parc 9ABOUYAWA';
$appShell = true; $activeNav = 'achats'; $pageHeading = 'Mes achats';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div><h1>Mes achats</h1><p>Présentez le code de chaque billet à l’accueil.</p></div>
  <a class="btn btn-amber" href="acheter.php"><i class="fa-solid fa-ticket"></i> Acheter des billets</a>
</div>
<?php showFlash(); ?>

<?php if (!$commandes): ?>
  <div class="empty-state">
    <span class="empty-icon"><i class="fa-solid fa-receipt"></i></span>
    <h2>Aucun achat pour le moment</h2>
    <p>Vos billets et vos paiements apparaîtront ici dès votre première commande.</p>
    <a class="btn btn-amber" href="acheter.php">Acheter des billets</a>
  </div>
<?php else: foreach ($commandes as $c): ?>
  <section class="panel order">
    <div class="order-head">
      <div><strong><?= e($c['num_commande']) ?></strong><small><?= e(date('d/m/Y à H:i', strtotime($c['date_commande']))) ?> · paiement <?= e($c['mode_paiement']) ?> (<?= e($c['statut_paiement']) ?>)</small></div>
      <strong><?= fmtPrix($c['montant_total']) ?></strong>
    </div>
    <?php if (!empty($billets[$c['id_commande']])): ?>
    <div class="tickets">
      <?php foreach ($billets[$c['id_commande']] as $b): ?>
      <article class="tkt">
        <div class="tkt-main">
          <div class="tkt-type"><i class="fa-solid fa-ticket" style="color:var(--amber-600)"></i><?= e($TICKETS[$b['type']]['nom'] ?? $b['type']) ?></div>
          <div class="tkt-code"><span class="code"><?= e($b['code_unique']) ?></span><button type="button" class="copy" data-copy="<?= e($b['code_unique']) ?>" aria-label="Copier le code <?= e($b['code_unique']) ?>"><i class="fa-regular fa-copy"></i></button></div>
          <div class="tkt-valid">Valable jusqu’au <?= e(date('d/m/Y', strtotime($b['date_validite']))) ?></div>
        </div>
        <div class="tkt-side"><b><?= fmtPrix($b['prix_applique']) ?></b><span class="status <?= billetStatutClass($b['statut']) ?>"><?= e(billetStatutLabel($b['statut'])) ?></span></div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <p class="hint" style="margin:0"><i class="fa-solid fa-calendar-check"></i> Cette commande correspond au paiement d’une réservation.</p>
    <?php endif; ?>
  </section>
<?php endforeach; endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
