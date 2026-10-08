<?php
require_once __DIR__ . '/auth.php';
$solidNav = $solidNav ?? true;
// $appShell = true : espace connecté (sidebar + topbar). Sinon : site public (navbar).
$appShell = !empty($appShell) && isLoggedIn();
$pageHeading = $pageHeading ?? '';
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Parc 9ABOUYAWA') ?></title>
  <meta name="description" content="Parc 9ABOUYAWA : attractions pour tous les âges, billets et réservation en ligne.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
  <!-- Icônes : Font Awesome 6 (à mentionner dans la documentation) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/app.css">
  <?php if (!empty($extraCss)): ?><link rel="stylesheet" href="<?= e($extraCss) ?>"><?php endif; ?>
</head>
<body class="<?= $appShell ? 'app-body' : '' ?>">
<a class="skip-link" href="#contenu">Aller au contenu</a>

<?php if ($appShell): $u = $_SESSION['user']; ?>
<div class="app">
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
  <aside class="sidebar" id="sidebar" aria-label="Navigation de l'espace">
    <a href="<?= BASE_URL ?>/index.php" class="brand"><span class="brand-logo"><i class="fa-solid fa-dice-d6"></i></span> Parc 9ABOUYAWA</a>
    <nav class="side-nav">
      <span class="side-label"><?= e(roleLabel($u['role'])) ?></span>
      <?php foreach (navItems($u['role']) as $it): ?>
        <a href="<?= e($it['href']) ?>" class="side-link <?= $activeNav === $it['key'] ? 'active' : '' ?>" <?= $activeNav === $it['key'] ? 'aria-current="page"' : '' ?>>
          <i class="fa-solid <?= e($it['icon']) ?>"></i><span><?= e($it['label']) ?></span>
        </a>
      <?php endforeach; ?>
      <span class="side-label">Site</span>
      <a href="<?= BASE_URL ?>/index.php#jeux" class="side-link"><i class="fa-solid fa-gamepad"></i><span>Nos jeux</span></a>
      <a href="<?= BASE_URL ?>/index.php#tarifs" class="side-link"><i class="fa-solid fa-tags"></i><span>Tarifs</span></a>
    </nav>
    <div class="side-user">
      <span class="avatar"><?= e(initiales($u['prenom'], $u['nom'])) ?></span>
      <div class="side-user-info"><strong><?= e($u['prenom'] . ' ' . $u['nom']) ?></strong><small><?= e(roleLabel($u['role'])) ?></small></div>
      <a href="<?= BASE_URL ?>/logout.php" class="side-logout" title="Déconnexion" aria-label="Déconnexion"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </aside>
  <div class="app-main">
    <header class="topbar">
      <button class="topbar-burger" id="sidebarToggle" aria-label="Ouvrir le menu" aria-controls="sidebar" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>
      <div class="crumbs"><a href="<?= BASE_URL ?>/<?= e($u['role']) ?>/dashboard.php">Mon espace</a><?php if ($pageHeading): ?><i class="fa-solid fa-chevron-right"></i><span><?= e($pageHeading) ?></span><?php endif; ?></div>
      <a class="topbar-site" href="<?= BASE_URL ?>/index.php"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span>Site public</span></a>
    </header>
    <main class="app-content" id="contenu">
<?php else: ?>
<header class="navbar <?= $solidNav ? 'solid' : '' ?>" id="navbar">
  <div class="container nav-inner">
    <a href="<?= BASE_URL ?>/index.php#accueil" class="brand"><span class="brand-logo"><i class="fa-solid fa-dice-d6"></i></span> Parc 9ABOUYAWA</a>
    <button class="nav-toggle" id="navToggle" aria-label="Ouvrir le menu" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>
    <nav class="nav-menu" id="navMenu" aria-label="Navigation principale">
      <a href="<?= BASE_URL ?>/index.php#accueil" class="nav-link">Accueil</a>
      <a href="<?= BASE_URL ?>/index.php#jeux" class="nav-link">Nos jeux</a>
      <a href="<?= BASE_URL ?>/index.php#tarifs" class="nav-link">Tarifs</a>
      <a href="<?= BASE_URL ?>/client/reserver.php" class="nav-link">Réservation</a>
      <a href="<?= BASE_URL ?>/index.php#contact" class="nav-link">Contact</a>
      <?php if (isLoggedIn()): ?>
        <a href="<?= BASE_URL ?>/<?= e($_SESSION['user']['role']) ?>/dashboard.php" class="nav-link">Mon espace (<?= e($_SESSION['user']['prenom']) ?>)</a>
        <a class="btn btn-amber" href="<?= BASE_URL ?>/logout.php">Déconnexion</a>
      <?php else: ?>
        <a class="btn btn-amber" href="<?= BASE_URL ?>/login.php">Se connecter</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="<?= $solidNav ? 'inner-main' : '' ?>" id="contenu">
<?php endif; ?>
