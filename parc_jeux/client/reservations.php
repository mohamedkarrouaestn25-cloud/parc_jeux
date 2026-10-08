<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/models.php';
checkRole(['client']);
$uid = $_SESSION['user']['id'];
$model = new ReservationModel($pdo);

/*
 * Règles documentées :
 * - Annulation : possible tant que la réservation est « confirmée » ou « en attente »
 *   et que la date de visite est postérieure à aujourd'hui (au moins 1 jour avant).
 * - Une réservation « payée » ne peut plus être annulée en ligne (contacter le parc).
 * - Paiement : simulation par carte, crée une commande et une recette.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $idRes = (int)($_POST['id'] ?? 0);
    $r = $model->findForClient($idRes, $uid);

    if (!csrfCheck()) {
        setFlash('error', CSRF_MSG);
    } elseif (!$r) {
        setFlash('error', 'Réservation introuvable.');
    } elseif ($action === 'annuler') {
        if (ReservationModel::peutAnnuler($r)) {
            $model->annuler($idRes);
            setFlash('success', 'Réservation annulée.');
        } else {
            setFlash('error', "Cette réservation ne peut plus être annulée en ligne.");
        }
    } elseif ($action === 'payer') {
        if ($r['statut'] !== 'confirmee') {
            setFlash('error', "Cette réservation ne peut pas être payée.");
        } else {
            try {
                $model->payer($r, $uid);
                setFlash('success', 'Paiement enregistré (simulation). Merci !');
            } catch (Throwable $ex) {
                setFlash('error', "Le paiement a échoué, veuillez réessayer.");
            }
        }
    }
    header('Location: reservations.php');
    exit;
}

$reservations = $model->forClient($uid);
$lignes = $model->lignesPour(array_column($reservations, 'id_reservation'));
$counts = array_count_values(array_column($reservations, 'statut'));

$pageTitle = 'Mes réservations – Parc 9ABOUYAWA';
$appShell = true; $activeNav = 'reservations'; $pageHeading = 'Mes réservations';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div><h1>Mes réservations</h1><p>Annulation possible jusqu’à la veille de la visite, tant que la réservation n’est pas payée.</p></div>
  <a class="btn btn-amber" href="reserver.php"><i class="fa-solid fa-calendar-plus"></i> Nouvelle réservation</a>
</div>
<?php showFlash(); ?>

<?php if (!$reservations): ?>
  <div class="empty-state">
    <span class="empty-icon"><i class="fa-regular fa-calendar-xmark"></i></span>
    <h2>Pas encore de réservation</h2>
    <p>Réservez vos jeux et vos horaires en quelques clics : votre journée apparaîtra ici.</p>
    <a class="btn btn-amber" href="reserver.php">Réserver</a>
  </div>
<?php else: ?>
<div data-filter-table>
  <div class="toolbar">
    <label class="search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" data-search placeholder="Rechercher un n° ou un jeu…" aria-label="Rechercher une réservation"></label>
    <div class="filters" style="margin:0">
      <button type="button" class="chip active" data-status-filter="all">Toutes <span class="count"><?= count($reservations) ?></span></button>
      <?php foreach (['confirmee', 'payee', 'en_attente', 'annulee'] as $s): if (empty($counts[$s])) continue; ?>
        <button type="button" class="chip" data-status-filter="<?= $s ?>"><?= e(statutResLabel($s)) ?><span class="count"><?= $counts[$s] ?></span></button>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="table-wrap"><table class="data stack">
    <thead><tr><th>N°</th><th>Visite</th><th>Pers.</th><th>Jeux</th><th>Montant</th><th>Statut</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($reservations as $r): $ls = $lignes[$r['id_reservation']] ?? []; ?>
      <tr data-status="<?= e($r['statut']) ?>" data-text="<?= e(strtolower($r['numero'] . ' ' . implode(' ', array_column($ls, 'nom')))) ?>">
        <td data-label="N°"><span class="code"><?= e($r['numero']) ?></span></td>
        <td data-label="Visite"><?= e(dateFr($r['date_visite'])) ?></td>
        <td data-label="Personnes"><?= (int)$r['nb_personnes'] ?></td>
        <td data-label="Jeux"><div class="cell-list"><?php foreach ($ls as $l): ?><span><?= e($l['nom']) ?> <small class="hint"><?= e(date('H:i', strtotime($l['creneau']))) ?></small></span><?php endforeach; ?></div></td>
        <td data-label="Montant"><strong><?= fmtPrix($r['montant']) ?></strong></td>
        <td data-label="Statut"><span class="status <?= statutResClass($r['statut']) ?>"><?= e(statutResLabel($r['statut'])) ?></span></td>
        <td class="actions">
          <?php if ($r['statut'] === 'confirmee'): ?>
            <form method="post" class="inline-form" data-confirm="Régler <?= e(fmtPrix($r['montant'])) ?> par carte (paiement simulé) pour la réservation <?= e($r['numero']) ?> ?" data-confirm-title="Payer la réservation" data-confirm-label="Payer <?= e(fmtPrix($r['montant'])) ?>">
              <?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$r['id_reservation'] ?>"><input type="hidden" name="action" value="payer"><button class="btn btn-pine btn-sm"><i class="fa-regular fa-credit-card"></i> Payer</button></form>
          <?php endif; ?>
          <?php if (ReservationModel::peutAnnuler($r)): ?>
            <form method="post" class="inline-form" data-variant="danger" data-confirm="La réservation <?= e($r['numero']) ?> sera annulée et les places libérées." data-confirm-title="Annuler cette réservation ?" data-confirm-label="Oui, annuler">
              <?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$r['id_reservation'] ?>"><input type="hidden" name="action" value="annuler"><button class="btn btn-danger btn-sm"><i class="fa-solid fa-ban"></i> Annuler</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div class="empty-inline" data-no-result hidden><i class="fa-solid fa-filter-circle-xmark"></i> Aucune réservation ne correspond à votre recherche.</div></div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
