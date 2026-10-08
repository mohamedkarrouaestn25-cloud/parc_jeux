<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/models.php';

$next = safeNext($_GET['next'] ?? $_POST['next'] ?? null);
if (isLoggedIn()) redirectByRole($_SESSION['user']['role']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrfCheck()) {
        $error = CSRF_MSG;
    } elseif ($email === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $u = (new UserModel($pdo))->findByEmail($email);
        if ($u && $u['statut'] === 'actif' && password_verify($password, $u['mot_de_passe'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int)$u['id_user'], 'nom' => $u['nom'],
                'prenom' => $u['prenom'], 'role' => $u['role'],
            ];
            if ($next) { header('Location: ' . $next); exit; }
            redirectByRole($u['role']);
        }
        $error = 'Email ou mot de passe incorrect, ou compte désactivé.';
    }
}
$pageTitle = 'Connexion – Parc 9ABOUYAWA';
$extraCss = BASE_URL . '/css/login.css';
require __DIR__ . '/includes/header.php';
?>
<section class="auth">
  <div class="auth-form-side">
    <div class="auth-box">
      <h1>Connexion</h1>
      <p class="auth-lead">Heureux de vous revoir. Connectez-vous pour accéder à votre espace.</p>

      <?php showFlash(); ?>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

      <form method="post" data-validate novalidate>
        <?= csrfField() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">

        <label for="email">Email</label>
        <div class="field">
          <i class="fa-regular fa-envelope"></i>
          <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="vous@exemple.com" autocomplete="email" required>
        </div>

        <label for="password">Mot de passe</label>
        <div class="field">
          <i class="fa-solid fa-key"></i>
          <input type="password" id="password" name="password" placeholder="Votre mot de passe" autocomplete="current-password" required>
          <button type="button" class="field-eye" data-toggle-pwd="password" aria-label="Afficher le mot de passe"><i class="fa-regular fa-eye"></i></button>
        </div>

        <button type="submit" class="btn btn-pine auth-submit">Se connecter <i class="fa-solid fa-arrow-right"></i></button>
      </form>

      <p class="link-alt">Pas encore de compte ? <a href="<?= BASE_URL ?>/register.php<?= $next ? '?next=' . urlencode($next) : '' ?>">Créer un compte</a></p>
    </div>
  </div>
  <?php require __DIR__ . '/includes/auth_visual.php'; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
