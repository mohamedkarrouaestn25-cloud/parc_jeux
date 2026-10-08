<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/models.php';
$TICKETS = require __DIR__ . '/config/tarifs.php';

$games = (new JeuModel($pdo))->all();
$cats = array_values(array_unique(array_column($games, 'categorie')));
$nbJeux = count($games);
$nbBillets = (new CommandeModel($pdo))->countBillets();
$nbRes = (new ReservationModel($pdo))->countActives();

$pageTitle = 'Parc 9ABOUYAWA – Parc de jeux';
$solidNav = false;
require __DIR__ . '/includes/header.php';
?>
  <section class="hero" id="accueil">
    <div class="container hero-content">
      <h1>Bienvenue dans notre parc de jeux</h1>
      <p class="hero-sub">Manèges, aventures en famille et espaces dédiés aux plus petits : une journée bien organisée, en toute sécurité, du billet à la sortie.</p>
      <div class="hero-actions">
        <button class="btn btn-amber" id="discoverBtn">Découvrir nos jeux</button>
        <a class="btn btn-outline" href="<?= BASE_URL ?>/client/reserver.php">Réserver maintenant</a>
      </div>
    </div>
  </section>

  <section class="section" id="jeux">
    <div class="container">
      <div class="section-head reveal">
        <h2>Nos jeux</h2>
        <p>Chaque attraction indique son âge minimum, sa capacité, la durée d’une session et son tarif.</p>
      </div>
      <div class="filters" id="filters">
        <button class="chip active" data-filter="all">Tous</button>
        <?php foreach ($cats as $c): ?>
          <button class="chip" data-filter="<?= e($c) ?>"><?= e($c) ?></button>
        <?php endforeach; ?>
      </div>
      <?php if (!$games): ?>
        <div class="empty-state"><span class="empty-icon"><i class="fa-solid fa-gamepad"></i></span><h2>Nos jeux arrivent bientôt</h2><p>Aucune attraction n'est publiée pour le moment.</p></div>
      <?php endif; ?>
      <div class="grid games-grid" id="gamesGrid">
        <?php foreach ($games as $g): ?>
        <article class="game reveal" data-cat="<?= e($g['categorie']) ?>">
          <div class="game-img">
            <img src="<?= BASE_URL ?>/<?= e($g['image'] ?: 'images/hero.jpg') ?>" alt="<?= e($g['nom']) ?>" loading="lazy">
            <span class="game-cat"><?= e($g['categorie']) ?></span>
          </div>
          <div class="game-body">
            <h3><?= e($g['nom']) ?></h3>
            <p><?= e($g['description']) ?></p>
            <ul class="game-meta">
              <li><i class="fa-solid fa-child"></i>Dès <?= (int)$g['age_minimum'] ?> ans</li>
              <li><i class="fa-solid fa-users"></i><?= (int)$g['capacite'] ?> places</li>
              <li><i class="fa-solid fa-stopwatch"></i><?= (int)$g['duree_session'] ?> min</li>
            </ul>
            <div class="game-foot">
              <span class="price"><?= fmtPrix($g['tarif']) ?></span>
              <span class="status <?= etatClass($g['etat']) ?>"><?= e(etatLabel($g['etat'])) ?></span>
            </div>
            <a class="btn btn-pine btn-sm" href="<?= BASE_URL ?>/jeu.php?id=<?= (int)$g['id_jeu'] ?>">Voir détails</a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="section section-tint" id="pourquoi">
    <div class="container">
      <div class="section-head reveal"><h2>Pourquoi nous choisir ?</h2></div>
      <div class="grid features-grid">
        <article class="feature reveal"><i class="fa-solid fa-gamepad"></i><h3>Jeux variés</h3><p>Du manège calme à l’attraction à sensations, chacun trouve son rythme.</p></article>
        <article class="feature reveal"><i class="fa-solid fa-shield-halved"></i><h3>Sécurité</h3><p>Contrôles quotidiens, maintenance suivie et personnel formé sur chaque jeu.</p></article>
        <article class="feature reveal"><i class="fa-solid fa-ticket"></i><h3>Réservation facile</h3><p>Choisissez votre date, vos billets et vos jeux en quelques clics.</p></article>
        <article class="feature reveal"><i class="fa-solid fa-face-smile"></i><h3>Expérience agréable</h3><p>Files d’attente maîtrisées, espaces de repos et accueil attentif.</p></article>
      </div>
    </div>
  </section>

  <section class="section" id="tarifs">
    <div class="container">
      <div class="section-head reveal">
        <h2>Tarifs</h2>
        <p>Tarifs en dirhams (DH). Les jeux se paient à la session, les billets donnent accès au parc.</p>
      </div>
      <div class="grid tickets-grid" id="ticketsGrid">
        <?php foreach ($TICKETS as $key => $t): $feat = !empty($t['badge']); ?>
        <article class="ticket reveal <?= $feat ? 'featured' : '' ?>">
          <?php if ($feat): ?><span class="ticket-badge"><?= e($t['badge']) ?></span><?php endif; ?>
          <h3><?= e($t['nom']) ?></h3>
          <div class="ticket-price"><?= (int)$t['prix'] ?> DH</div>
          <span class="ticket-note"><?= e($t['note']) ?></span>
          <p><?= e($t['description']) ?></p>
          <a class="btn <?= $feat ? 'btn-amber' : 'btn-pine' ?>" href="<?= BASE_URL ?>/client/acheter.php?type=<?= e($key) ?>">Acheter</a>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="stats" id="stats">
    <div class="container grid stats-grid">
      <div class="stat"><strong data-count="<?= $nbJeux ?>">0</strong><span>Jeux et attractions</span></div>
      <div class="stat"><strong data-count="<?= $nbBillets ?>">0</strong><span>Billets vendus</span></div>
      <div class="stat"><strong data-count="<?= $nbRes ?>">0</strong><span>Réservations</span></div>
      <div class="stat"><strong data-count="12">0</strong><span>Années d’expérience</span></div>
    </div>
  </section>

  <section class="section" id="reservation">
    <div class="container">
      <div class="cta reveal">
        <h2>Prêt pour une journée pleine de divertissement ?</h2>
        <p>Réservez vos jeux à l’avance et profitez du parc sans attendre.</p>
        <a class="btn btn-amber" href="<?= BASE_URL ?>/client/reserver.php">Réserver maintenant</a>
      </div>
    </div>
  </section>

  <section class="section section-tint" id="contact">
    <div class="container contact-grid">
      <div class="reveal">
        <h2>Contact</h2>
        <ul class="contact-list">
          <li><i class="fa-solid fa-location-dot"></i> HAY TANGER _ 9ABOUYAWA</li>
          <li><i class="fa-solid fa-phone"></i> +212698437891</li>
          <li><i class="fa-solid fa-envelope"></i> contact@9abouyawa.example</li>
          <li><i class="fa-solid fa-clock"></i> Tous les jours, 10h – 20h</li>
        </ul>
        <div class="socials">
          <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
        </div>
      </div>
      <form class="contact-form reveal" id="contactForm" novalidate>
        <label for="nom">Nom</label>
        <input type="text" id="nom" aria-describedby="err-nom" name="nom" autocomplete="name">
        <small class="error" id="err-nom" data-for="nom"></small>
        <label for="email">Email</label>
        <input type="email" id="email" aria-describedby="err-email" name="email" autocomplete="email">
        <small class="error" id="err-email" data-for="email"></small>
        <label for="message">Message</label>
        <textarea id="message" aria-describedby="err-message" name="message" rows="4"></textarea>
        <small class="error" id="err-message" data-for="message"></small>
        <button type="submit" class="btn btn-pine">Envoyer</button>
      </form>
    </div>
  </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
