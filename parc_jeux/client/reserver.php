<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/models.php';
checkRole(['client']);

$games = (new JeuModel($pdo))->reservables();
$byId = array_column($games, null, 'id_jeu');
$creneaux = creneauxDispo();
$errors = [];

$date = $_POST['date_visite'] ?? date('Y-m-d', strtotime('+1 day'));
$nb = (int)($_POST['nb_personnes'] ?? 1);
$sel = $_POST['jeux'] ?? [];
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['jeu'])) {
    $sel = [(int)$_GET['jeu'] => '10:00'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck()) {
        $errors[] = CSRF_MSG;
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            $errors[] = 'Date de visite invalide.';
        } elseif ($date < date('Y-m-d')) {
            $errors[] = 'La date de visite ne peut pas être dans le passé.';
        }
        if ($nb < 1 || $nb > 50) $errors[] = 'Le nombre de personnes doit être entre 1 et 50.';

        $choix = [];
        foreach ((array)$sel as $id => $c) {
            $id = (int)$id;
            if ($c === '') continue;
            if (!isset($byId[$id]) || !in_array($c, $creneaux, true)) { $errors[] = 'Choix de jeu ou de créneau invalide.'; continue; }
            $choix[$id] = $c;
        }
        if (!$choix) $errors[] = 'Choisissez au moins un jeu avec un créneau.';

        if (!$errors) {
            try {
                (new ReservationModel($pdo))->creer($_SESSION['user']['id'], $date, $nb, $choix);
                setFlash('success', 'Réservation confirmée. Vous pouvez la payer depuis cette page.');
                header('Location: reservations.php');
                exit;
            } catch (RuntimeException $ex) {
                $errors[] = $ex->getMessage();
            } catch (Throwable $ex) {
                $errors[] = "Une erreur est survenue, veuillez réessayer.";
            }
        }
    }
}

$pageTitle = 'Réservation – Parc 9ABOUYAWA';
$appShell = true; $activeNav = 'reserver'; $pageHeading = 'Réserver';
$extraJs = BASE_URL . '/js/planner.js';
require __DIR__ . '/../includes/header.php';
?>
<noscript><style>.pick select.slot-select { display: block !important; }</style></noscript>
<div class="page-head">
  <div><h1>Planifier ma journée</h1><p>Choisissez la date et le nombre de personnes, puis un créneau pour chaque jeu. Votre programme se construit en direct. Le parc ouvre de 10h à 20h.</p></div>
</div>
<?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<form method="post" class="planner" id="formReserver" data-base="<?= BASE_URL ?>" data-validate novalidate>
  <?= csrfField() ?>
  <div>
    <section class="panel">
      <div class="planner-fields form-wide">
        <div><label for="date_visite">Date de visite</label>
          <input type="date" id="date_visite" name="date_visite" value="<?= e($date) ?>" min="<?= date('Y-m-d') ?>" required></div>
        <div><label for="nb_personnes">Nombre de personnes</label>
          <input type="number" id="nb_personnes" name="nb_personnes" value="<?= (int)$nb ?>" min="1" max="50" required>
          <p class="hint">Groupe ou famille (1 à 50).</p></div>
      </div>
    </section>

    <div class="game-pick">
      <?php if (!$games): ?>
        <div class="empty-state"><span class="empty-icon"><i class="fa-solid fa-wrench"></i></span><h2>Aucun jeu réservable</h2><p>Tous les jeux sont momentanément indisponibles. Revenez bientôt !</p></div>
      <?php endif; ?>
      <?php foreach ($games as $g): $cur = $sel[$g['id_jeu']] ?? ''; ?>
      <article class="pick">
        <img class="pick-img" src="<?= BASE_URL ?>/<?= e($g['image'] ?: 'images/hero.jpg') ?>" alt="" loading="lazy">
        <div>
          <div class="pick-head"><h4><?= e($g['nom']) ?></h4><span class="pick-price"><?= fmtPrix($g['tarif']) ?> <small>/ pers.</small></span></div>
          <div class="pick-meta">
            <span><i class="fa-solid fa-child"></i>Dès <?= (int)$g['age_minimum'] ?> ans</span>
            <span><i class="fa-solid fa-users"></i><?= (int)$g['capacite'] ?> places / session</span>
            <span><i class="fa-solid fa-stopwatch"></i><?= (int)$g['duree_session'] ?> min</span>
          </div>
          <div class="slots" role="group" aria-label="Créneaux pour <?= e($g['nom']) ?>"></div>
          <!-- Valeur réellement envoyée : pilotée par les boutons de créneau (planner.js) -->
          <select class="slot-select" name="jeux[<?= (int)$g['id_jeu'] ?>]" data-id="<?= (int)$g['id_jeu'] ?>" data-nom="<?= e($g['nom']) ?>"
                  data-prix="<?= e($g['tarif']) ?>" data-duree="<?= (int)$g['duree_session'] ?>" data-cap="<?= (int)$g['capacite'] ?>" aria-label="Créneau pour <?= e($g['nom']) ?>">
            <option value="">— Non —</option>
            <?php foreach ($creneaux as $c): ?><option value="<?= $c ?>" <?= $cur === $c ? 'selected' : '' ?>><?= $c ?></option><?php endforeach; ?>
          </select>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>

  <aside class="panel day-card" aria-live="polite">
    <div class="day-head">
      <span class="eyebrow">Ma journée</span>
      <strong id="dayDate">—</strong>
      <small id="dayPeople"></small>
    </div>
    <div class="day-body">
      <ol class="timeline" id="dayList"></ol>
      <div class="day-sum"><span id="daySummary">Aucun jeu choisi</span></div>
      <div class="day-total"><span>Montant total</span><strong id="total">0 DH</strong></div>
      <button type="submit" class="btn btn-amber" id="daySubmit" disabled>Confirmer la réservation</button>
    </div>
  </aside>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
