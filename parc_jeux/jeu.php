<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/models.php';

$id = (int)($_GET['id'] ?? 0);
$g = (new JeuModel($pdo))->find($id);
if (!$g) {
    abortPage(404, 'Jeu introuvable', "Ce jeu n'existe pas ou n'est plus proposé.");
}
$reservable = JeuModel::estReservable($g);
$pageTitle = $g['nom'] . ' – Parc 9ABOUYAWA';
require __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="container detail-grid">
    <img src="<?= BASE_URL ?>/<?= e($g['image'] ?: 'images/hero.jpg') ?>" alt="<?= e($g['nom']) ?>">
    <div>
      <span class="game-cat" style="position:static;display:inline-block;margin-bottom:10px"><?= e($g['categorie']) ?></span>
      <h2><?= e($g['nom']) ?></h2>
      <p><?= e($g['description']) ?></p>
      <ul class="detail-list">
        <li><i class="fa-solid fa-child"></i>Âge minimum : <?= (int)$g['age_minimum'] ?> ans</li>
        <li><i class="fa-solid fa-users"></i>Capacité : <?= (int)$g['capacite'] ?> places par session</li>
        <li><i class="fa-solid fa-stopwatch"></i>Durée d’une session : <?= (int)$g['duree_session'] ?> min</li>
        <li><i class="fa-solid fa-tag"></i>Tarif : <strong><?= fmtPrix($g['tarif']) ?></strong> par personne</li>
        <?php if ($g['date_mise_en_service']): ?>
        <li><i class="fa-solid fa-calendar"></i>En service depuis le <?= e(date('d/m/Y', strtotime($g['date_mise_en_service']))) ?></li>
        <?php endif; ?>
      </ul>
      <p><span class="status <?= etatClass($g['etat']) ?>"><?= e(etatLabel($g['etat'])) ?></span></p>
      <div class="btn-row">
        <?php if ($reservable): ?>
          <a class="btn btn-amber" href="<?= BASE_URL ?>/client/reserver.php?jeu=<?= (int)$g['id_jeu'] ?>">Réserver ce jeu</a>
        <?php else: ?>
          <span class="alert alert-info" style="margin:0">Ce jeu n’est pas réservable pour le moment.</span>
        <?php endif; ?>
        <a class="btn btn-pine" href="<?= BASE_URL ?>/index.php#jeux">Retour aux jeux</a>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
