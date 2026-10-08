"use strict";

/* ===== Utilitaires ===== */
const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
const fmtPrix = (n) => `${Math.round(n).toLocaleString("fr-FR")} DH`;

let toastTimer;
function showToast(message, type = "success") {
  const toast = $("#toast");
  if (!toast) return;
  toast.textContent = message;
  toast.className = `toast show ${type}`;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove("show"), 3500);
}

function scrollToId(id) {
  const target = document.getElementById(id);
  if (target) target.scrollIntoView({ behavior: "smooth" });
}

/* ===== Navigation ===== */
function initNav() {
  const navbar = $("#navbar"), menu = $("#navMenu"), toggle = $("#navToggle");
  if (!navbar || !menu || !toggle) return; // espace connecté : pas de navbar publique
  const setMenu = (open) => {
    menu.classList.toggle("open", open);
    navbar.classList.toggle("menu-open", open);
    toggle.setAttribute("aria-expanded", open);
    toggle.innerHTML = `<i class="fa-solid ${open ? "fa-xmark" : "fa-bars"}"></i>`;
  };
  toggle.addEventListener("click", () => setMenu(!menu.classList.contains("open")));
  $$(".nav-link").forEach((l) => l.addEventListener("click", () => setMenu(false)));
  document.addEventListener("keydown", (e) => { if (e.key === "Escape") setMenu(false); });

  const onScroll = () => navbar.classList.toggle("scrolled", window.scrollY > 40);
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  // Lien actif selon la section visible (page d'accueil uniquement)
  const ids = ["accueil", "jeux", "tarifs", "reservation", "contact"];
  const sections = ids.map((id) => document.getElementById(id)).filter(Boolean);
  if (!sections.length) return;
  const links = $$(".nav-link");
  const spy = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (e.isIntersecting) links.forEach((l) => l.classList.toggle("active", l.getAttribute("href").endsWith(`#${e.target.id}`)));
    });
  }, { rootMargin: "-45% 0px -50% 0px" });
  sections.forEach((s) => spy.observe(s));
}

/* ===== Page d'accueil : bouton + filtres par catégorie ===== */
function initHome() {
  const discover = $("#discoverBtn");
  if (discover) discover.addEventListener("click", () => scrollToId("jeux"));

  const filters = $("#filters");
  if (!filters) return;
  filters.addEventListener("click", (e) => {
    const chip = e.target.closest(".chip");
    if (!chip) return;
    $$(".chip", filters).forEach((c) => c.classList.toggle("active", c === chip));
    const cat = chip.dataset.filter;
    $$("#gamesGrid .game").forEach((g) => g.classList.toggle("hidden", cat !== "all" && g.dataset.cat !== cat));
  });
}

/* ===== Animations au scroll + compteurs ===== */
function initReveal() {
  const io = new IntersectionObserver((entries, obs) => {
    entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add("visible"); obs.unobserve(e.target); } });
  }, { threshold: 0.12 });
  $$(".reveal").forEach((el, i) => { el.style.setProperty("--d", `${(i % 4) * 80}ms`); io.observe(el); });
}

function animateCount(el) {
  const target = Number(el.dataset.count), suffix = el.dataset.suffix || "", duration = 1800, start = performance.now();
  const step = (now) => {
    const p = Math.min((now - start) / duration, 1);
    const eased = 1 - Math.pow(1 - p, 3);
    el.textContent = Math.round(target * eased).toLocaleString("fr-FR") + (p === 1 ? suffix : "");
    if (p < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
}

function initCounters() {
  const stats = $("#stats");
  if (!stats) return;
  const io = new IntersectionObserver((entries, obs) => {
    entries.forEach((e) => {
      if (e.isIntersecting) { $$("[data-count]", e.target).forEach(animateCount); obs.unobserve(e.target); }
    });
  }, { threshold: 0.4 });
  io.observe(stats);
}

/* ===== Formulaire de contact (validation côté client) ===== */
function initContactForm() {
  const form = $("#contactForm");
  if (!form) return;
  const rules = {
    nom: (v) => (v.trim().length < 2 ? "Entrez votre nom (2 caractères minimum)." : ""),
    email: (v) => (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? "" : "Entrez une adresse email valide."),
    message: (v) => (v.trim().length < 10 ? "Votre message doit contenir au moins 10 caractères." : "")
  };
  const check = (name) => {
    const field = form.elements[name], msg = rules[name](field.value);
    $(`[data-for="${name}"]`, form).textContent = msg;
    field.classList.toggle("invalid", Boolean(msg));
    field.setAttribute("aria-invalid", String(Boolean(msg)));
    return !msg;
  };
  Object.keys(rules).forEach((n) => form.elements[n].addEventListener("input", () => check(n)));
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const valid = Object.keys(rules).map(check).every(Boolean);
    if (!valid) return showToast("Veuillez corriger les champs signalés.", "info");
    // Plus tard : envoi vers un script PHP (fetch / POST)
    showToast("Merci ! Votre message a bien été envoyé.");
    form.reset();
  });
}

/* ===== Validation générique des formulaires PHP (data-validate) ===== */
function initFormValidation() {
  $$("form[data-validate]").forEach((form) => {
    form.addEventListener("submit", (e) => {
      let ok = true;
      $$("[required]", form).forEach((f) => {
        const bad = !f.value.trim() || (f.type === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.value))
          || (f.type === "number" && (Number(f.value) < Number(f.min || 1) || Number(f.value) > Number(f.max || 1e9)));
        f.classList.toggle("invalid", bad);
        if (bad) ok = false;
      });
      if (!ok) { e.preventDefault(); showToast("Veuillez corriger les champs signalés.", "info"); }
    });
  });
}

/* ===== Calcul du montant total (réservation et achat) ===== */
function initTotals() {
  const total = $("#total");
  if (!total) return;

  const fr = $("#formReserver");
  if (fr) {
    const calc = () => {
      const nb = Number(fr.elements["nb_personnes"].value) || 0;
      let t = 0;
      $$("select[data-prix]", fr).forEach((s) => { if (s.value) t += Number(s.dataset.prix) * nb; });
      total.textContent = fmtPrix(t);
    };
    fr.addEventListener("input", calc);
    fr.addEventListener("change", calc);
    calc();
  }

  const fa = $("#formAcheter");
  if (fa) {
    const calc = () => {
      const opt = fa.elements["type"].selectedOptions[0];
      const q = Number(fa.elements["quantite"].value) || 0;
      total.textContent = fmtPrix(Number(opt.dataset.prix) * q);
    };
    fa.addEventListener("input", calc);
    fa.addEventListener("change", calc);
    calc();
  }
}

/* ===== Initialisation ===== */
document.addEventListener("DOMContentLoaded", () => {
  const year = $("#year");
  if (year) year.textContent = new Date().getFullYear();
  initNav();
  initHome();
  initReveal();
  initCounters();
  initContactForm();
  initFormValidation();
  initTotals();
});
