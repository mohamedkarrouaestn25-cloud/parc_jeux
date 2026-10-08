<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/models.php';
checkRole(['client']);
$TICKETS = require __DIR__ . '/../config/tarifs.php';
$uid = $_SESSION['user']['id'];

$type = $_POST['type'] ?? ($_GET['type'] ?? 'adulte');
$qte = (int)($_POST['quantite'] ?? 1);
$date = $_POST['date_visite'] ?? date('Y-m-d');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck()) {
        $errors[] = CSRF_MSG;
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!isset($TICKETS[$type])) $errors[] = 'Type de billet invalide.';
        if ($qte < 1 || $qte > 20) $errors[] = 'La quantité doit être entre 1 et 20.';
        if (!$d || $d->format('Y-m-d') !== $date) $errors[] = 'Date invalide.';
        elseif ($date < date('Y-m-d')) $errors[] = 'La date ne peut pas être dans le passé.';

        if (!$errors) {
            try {
                (new CommandeModel($pdo))->acheterBillets($uid, $type, $TICKETS[$type], $qte, $date);
                setFlash('success', "Achat enregistré (paiement simulé) : $qte billet(s), " . fmtPrix($TICKETS[$type]['prix'] * $qte) . '.');
                header('Location: achats.php');
                exit;
            } catch (Throwable $ex) {
                $errors[] = "L'achat a échoué, veuillez réessayer.";
            }
        }
    }
}

$pageTitle = 'Acheter des billets – Parc 9ABOUYAWA';
$appShell = true; $activeNav = 'acheter'; $pageHeading = 'Acheter des billets';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div><h1>Acheter des billets</h1><p>Le paiement en ligne est simulé dans le cadre du projet.</p></div>
</div>
<?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<form method="post" class="checkout" id="formAcheter" data-validate novalidate>
  <?= csrfField() ?>
  <section class="panel">
    <div class="field-row"><label for="type">Type de billet</label>
      <select id="type" name="type">
        <?php foreach ($TICKETS as $k => $t): ?>
          <option value="<?= e($k) ?>" data-prix="<?= (int)$t['prix'] ?>" data-nom="<?= e($t['nom']) ?>" data-desc="<?= e($t['description']) ?>" data-note="<?= e($t['prix'] . ' DH · ' . $t['note']) ?>" <?= $type === $k ? 'selected' : '' ?>><?= e($t['nom']) ?> – <?= (int)$t['prix'] ?> DH (<?= e($t['note']) ?>)</option>
        <?php endforeach; ?>
      </select></div>
    <div class="field-row"><label for="quantite">Quantité</label>
      <input type="number" id="quantite" name="quantite" min="1" max="20" value="<?= (int)$qte ?>" required>
      <p class="hint">Jusqu’à 20 billets par commande.</p></div>
    <div class="field-row"><label for="date_visite">Date de visite (ou de début)</label>
      <input type="date" id="date_visite" name="date_visite" min="<?= date('Y-m-d') ?>" value="<?= e($date) ?>" required></div>
  </section>

  <aside class="panel summary">
    <div class="day-head"><span class="eyebrow">Récapitulatif</span><strong id="sumName">—</strong><small id="sumNote"></small></div>
    <div class="day-body">
      <p id="sumDesc"></p>
      <div class="day-total"><span>Total</span><strong id="total">0 DH</strong></div>
      <button type="submit" class="btn btn-amber"><i class="fa-regular fa-credit-card"></i> Payer (simulation)</button>
    </div>
  </aside>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
