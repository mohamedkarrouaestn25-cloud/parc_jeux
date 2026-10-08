"use strict";
/* Comportements transverses de l'interface (chargé après script.js) :
   sidebar mobile, alertes, modale de confirmation, états de chargement,
   recherche/filtre de tableaux, copie de codes, récapitulatif d'achat. */
(function () {
  const q = (s, r = document) => r.querySelector(s);
  const qa = (s, r = document) => [...r.querySelectorAll(s)];

  /* ----- Sidebar (mobile) ----- */
  const sidebar = q("#sidebar"), burger = q("#sidebarToggle"), backdrop = q("#sidebarBackdrop");
  if (sidebar && burger) {
    const set = (open) => {
      sidebar.classList.toggle("open", open);
      backdrop.classList.toggle("show", open);
      burger.setAttribute("aria-expanded", String(open));
    };
    burger.addEventListener("click", () => set(!sidebar.classList.contains("open")));
    backdrop.addEventListener("click", () => set(false));
    document.addEventListener("keydown", (e) => { if (e.key === "Escape") set(false); });
  }

  /* ----- Alertes : icône + bouton de fermeture ----- */
  const icons = { success: "fa-circle-check", error: "fa-circle-exclamation", info: "fa-circle-info" };
  qa(".alert").forEach((a) => {
    const type = Object.keys(icons).find((t) => a.classList.contains("alert-" + t));
    if (type) a.insertAdjacentHTML("afterbegin", `<i class="fa-solid ${icons[type]}" aria-hidden="true"></i>`);
    if (!a.hasAttribute("data-keep")) {
      const x = document.createElement("button");
      x.type = "button"; x.className = "alert-x"; x.setAttribute("aria-label", "Fermer");
      x.innerHTML = '<i class="fa-solid fa-xmark"></i>';
      x.addEventListener("click", () => a.remove());
      a.appendChild(x);
    }
    if (type === "error" || type === "success") a.setAttribute("role", type === "error" ? "alert" : "status");
  });

  /* ----- Modale de confirmation : <form data-confirm="Message" data-confirm-title data-confirm-label data-variant="danger"> ----- */
  const modal = q("#confirmModal");
  let pending = null, lastFocus = null;
  const closeModal = () => { modal.hidden = true; pending = null; if (lastFocus) lastFocus.focus(); };
  if (modal) {
    q("#modalCancel").addEventListener("click", closeModal);
    modal.addEventListener("click", (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener("keydown", (e) => { if (e.key === "Escape" && !modal.hidden) closeModal(); });
    q("#modalOk").addEventListener("click", () => {
      const form = pending; modal.hidden = true; pending = null;
      if (form) { form.dataset.confirmed = "1"; form.requestSubmit(); }
    });
  }
  document.addEventListener("submit", (e) => {
    const form = e.target;
    if (!modal || !form.matches("[data-confirm]") || form.dataset.confirmed === "1") return;
    e.preventDefault();
    pending = form; lastFocus = document.activeElement;
    const danger = form.dataset.variant === "danger";
    q("#modalTitle").textContent = form.dataset.confirmTitle || "Confirmer l'action";
    q("#modalText").textContent = form.dataset.confirm;
    const ok = q("#modalOk");
    ok.textContent = form.dataset.confirmLabel || "Confirmer";
    ok.className = "btn " + (danger ? "btn-danger" : "btn-pine");
    const ic = q("#modalIcon");
    ic.className = "modal-icon" + (danger ? " danger" : "");
    ic.innerHTML = `<i class="fa-solid ${danger ? "fa-triangle-exclamation" : "fa-circle-question"}"></i>`;
    modal.hidden = false; ok.focus();
  }, true);

  /* ----- État de chargement + anti double-clic sur les formulaires POST ----- */
  document.addEventListener("submit", (e) => {
    const form = e.target;
    if (e.defaultPrevented || form.method.toLowerCase() !== "post") return;
    const btn = e.submitter || q('button[type="submit"], button:not([type])', form);
    if (!btn) return;
    setTimeout(() => { btn.classList.add("is-loading"); btn.disabled = true; }, 0);
  });
  window.addEventListener("pageshow", () => qa(".is-loading").forEach((b) => { b.classList.remove("is-loading"); b.disabled = false; }));

  /* ----- Recherche + filtre par statut : <div data-filter-table> avec [data-search], [data-status-filter], tr[data-status][data-text] ----- */
  qa("[data-filter-table]").forEach((box) => {
    const input = q("[data-search]", box), chips = qa("[data-status-filter]", box);
    const rows = qa("tr[data-status]", box), empty = q("[data-no-result]", box);
    let status = "all";
    const apply = () => {
      const term = (input ? input.value : "").trim().toLowerCase();
      let shown = 0;
      rows.forEach((r) => {
        const ok = (status === "all" || r.dataset.status === status) && (!term || r.dataset.text.toLowerCase().includes(term));
        r.hidden = !ok; if (ok) shown++;
      });
      if (empty) empty.hidden = shown !== 0;
    };
    if (input) input.addEventListener("input", apply);
    chips.forEach((c) => c.addEventListener("click", () => {
      status = c.dataset.statusFilter;
      chips.forEach((x) => x.classList.toggle("active", x === c));
      apply();
    }));
  });

  /* ----- Copier un code ----- */
  qa("[data-copy]").forEach((b) => b.addEventListener("click", async () => {
    try {
      await navigator.clipboard.writeText(b.dataset.copy);
      if (window.showToast) showToast("Code copié : " + b.dataset.copy);
    } catch (_) { if (window.showToast) showToast("Copie impossible sur ce navigateur.", "info"); }
  }));

  /* ----- Achat : récapitulatif dynamique du billet choisi ----- */
  const fa = q("#formAcheter");
  if (fa) {
    const sel = fa.elements["type"], name = q("#sumName"), desc = q("#sumDesc"), note = q("#sumNote");
    const upd = () => {
      const o = sel.selectedOptions[0];
      name.textContent = o.dataset.nom; desc.textContent = o.dataset.desc; note.textContent = o.dataset.note;
    };
    sel.addEventListener("change", upd); upd();
  }
})();

/* ----- Afficher / masquer le mot de passe : <button data-toggle-pwd="id_du_champ"> ----- */
document.querySelectorAll("[data-toggle-pwd]").forEach((btn) => {
  const input = document.getElementById(btn.dataset.togglePwd);
  if (!input) return;
  btn.addEventListener("click", () => {
    const show = input.type === "password";
    input.type = show ? "text" : "password";
    btn.innerHTML = `<i class="fa-regular ${show ? "fa-eye-slash" : "fa-eye"}"></i>`;
    btn.setAttribute("aria-label", show ? "Masquer le mot de passe" : "Afficher le mot de passe");
  });
});
