<?php
// Inscription d'un CLIENT (les autres rôles sont créés par l'admin)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/models.php';

$next = safeNext($_GET['next'] ?? $_POST['next'] ?? null);
$errors = [];
$v = ['nom' => '', 'prenom' => '', 'email' => '', 'telephone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');
    $pw = $_POST['password'] ?? '';
    $users = new UserModel($pdo);

    if (!csrfCheck()) {
        $errors[] = CSRF_MSG;
    } else {
        if ($v['nom'] === '' || $v['prenom'] === '' || $v['email'] === '' || $pw === '') {
            $errors[] = 'Veuillez remplir les champs obligatoires.';
        }
        if ($v['email'] !== '' && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse email invalide.';
        }
        if ($v['telephone'] !== '' && !preg_match('/^[0-9+ ]{8,20}$/', $v['telephone'])) {
            $errors[] = 'Numéro de téléphone invalide.';
        }
        if ($pw !== '' && strlen($pw) < 6) {
            $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
        }
        if (!$errors) {
            if ($users->emailExists($v['email'])) {
                $errors[] = 'Cet email est déjà utilisé.';
            } else {
                $users->createClient($v, $pw);
                setFlash('success', 'Compte créé. Vous pouvez vous connecter.');
                header('Location: ' . BASE_URL . '/login.php' . ($next ? '?next=' . urlencode($next) : ''));
                exit;
            }
        }
    }
}
$pageTitle = 'Inscription – Parc 9ABOUYAWA';
$extraCss = BASE_URL . '/css/login.css';
require __DIR__ . '/includes/header.php';
?>
<section class="auth">
  <div class="auth-form-side">
    <div class="auth-box">
      <h1>Créer un compte</h1>
      <p class="auth-lead">Un compte client pour réserver vos jeux et acheter vos billets en quelques clics.</p>
      <?php showFlash(); ?>
      <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      <form method="post" data-validate novalidate>
        <?= csrfField() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="field-grid">
          <div><label for="nom">Nom</label><div class="field"><i class="fa-regular fa-user"></i><input id="nom" name="nom" value="<?= e($v['nom']) ?>" autocomplete="family-name" required></div></div>
          <div><label for="prenom">Prénom</label><div class="field"><i class="fa-regular fa-user"></i><input id="prenom" name="prenom" value="<?= e($v['prenom']) ?>" autocomplete="given-name" required></div></div>
        </div>
        <label for="email">Email</label>
        <div class="field"><i class="fa-regular fa-envelope"></i><input type="email" id="email" name="email" value="<?= e($v['email']) ?>" placeholder="vous@exemple.com" autocomplete="email" required></div>
        <label for="telephone">Téléphone <small class="opt">(facultatif)</small></label>
        <div class="field"><i class="fa-solid fa-phone"></i><input id="telephone" name="telephone" value="<?= e($v['telephone']) ?>" autocomplete="tel"></div>
        <label for="password">Mot de passe <small class="opt">(6 caractères min.)</small></label>
        <div class="field">
          <i class="fa-solid fa-key"></i>
          <input type="password" id="password" name="password" autocomplete="new-password" required>
          <button type="button" class="field-eye" data-toggle-pwd="password" aria-label="Afficher le mot de passe"><i class="fa-regular fa-eye"></i></button>
        </div>
        <button type="submit" class="btn btn-pine auth-submit">Créer mon compte <i class="fa-solid fa-arrow-right"></i></button>
      </form>
      <p class="link-alt">Déjà inscrit ? <a href="<?= BASE_URL ?>/login.php<?= $next ? '?next=' . urlencode($next) : '' ?>">Se connecter</a></p>
    </div>
  </div>
  <?php require __DIR__ . '/includes/auth_visual.php'; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
