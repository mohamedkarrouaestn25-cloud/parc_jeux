<?php $appShell = !empty($appShell) && isLoggedIn(); ?>
<?php if ($appShell): ?>
    </main>
  </div><!-- /.app-main -->
</div><!-- /.app -->
<?php else: ?>
</main>

<footer class="footer">
  <div class="container footer-grid">
    <div>
      <a href="<?= BASE_URL ?>/index.php#accueil" class="brand"><span class="brand-logo"><i class="fa-solid fa-dice-d6"></i></span> Parc 9ABOUYAWA</a>
      <p>Un parc de jeux pour les familles, les groupes et les passionnés de sensations.</p>
    </div>
    <div>
      <h4>Liens rapides</h4>
      <a class="footer-link" href="<?= BASE_URL ?>/index.php#jeux">Nos jeux</a>
      <a class="footer-link" href="<?= BASE_URL ?>/index.php#tarifs">Tarifs</a>
      <a class="footer-link" href="<?= BASE_URL ?>/client/reserver.php">Réservation</a>
      <a class="footer-link" href="<?= BASE_URL ?>/index.php#contact">Contact</a>
    </div>
    <div>
      <h4>Contact</h4>
      <a class="footer-link" href="<?= BASE_URL ?>/index.php#contact">contact@9abouyawa.example</a>
      <a class="footer-link" href="<?= BASE_URL ?>/index.php#contact">+212 698 437 891</a>
      <div class="socials">
        <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
        <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
        <a href="#" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
      </div>
    </div>
  </div>
  <p class="copyright">© <span id="year">2026</span> Parc 9ABOUYAWA. Projet universitaire, tous droits réservés.</p>
</footer>
<?php endif; ?>

<!-- Modale de confirmation (utilisée par les formulaires portant data-confirm) -->
<div class="modal" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" hidden>
  <div class="modal-box">
    <span class="modal-icon" id="modalIcon"><i class="fa-solid fa-circle-question"></i></span>
    <h3 id="modalTitle">Confirmer</h3>
    <p id="modalText"></p>
    <div class="modal-actions">
      <button type="button" class="btn btn-ghost" id="modalCancel">Retour</button>
      <button type="button" class="btn btn-pine" id="modalOk">Confirmer</button>
    </div>
  </div>
</div>

<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="<?= BASE_URL ?>/js/script.js"></script>
<script src="<?= BASE_URL ?>/js/app.js"></script>
<?php if (!empty($extraJs)): ?><script src="<?= e($extraJs) ?>"></script><?php endif; ?>
</body>
</html>
