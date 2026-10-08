"use strict";
/* Planificateur de journée (réservation) :
   - chaque jeu propose ses créneaux sous forme de boutons avec les places restantes (temps réel),
   - la « Ma journée » se construit en direct sous forme de frise chronologique (heure de début/fin, prix, total).
   Les <select name="jeux[id]"> restent la source de vérité envoyée au serveur : aucun champ du formulaire n'a changé. */
(function () {
  const form = document.getElementById("formReserver");
  if (!form) return;
  const q = (s, r = document) => r.querySelector(s);
  const qa = (s, r = document) => [...r.querySelectorAll(s)];
  const money = (n) => `${Math.round(n).toLocaleString("fr-FR")} DH`;
  const hhmm = (min) => `${String(Math.floor(min / 60) % 24).padStart(2, "0")}:${String(min % 60).padStart(2, "0")}`;
  const toMin = (h) => { const [a, b] = h.split(":").map(Number); return a * 60 + b; };

  const dateInput = form.elements["date_visite"], nbInput = form.elements["nb_personnes"];
  const picks = qa(".pick", form).map((el) => {
    const select = q("select.slot-select", el), box = q(".slots", el);
    return { el, select, box, id: select.dataset.id, nom: select.dataset.nom, prix: Number(select.dataset.prix), duree: Number(select.dataset.duree), cap: Number(select.dataset.cap), slots: {} };
  });
  let lastSig = null;
  let pris = {}; // places déjà prises : { idJeu: { "10:00": 12 } }

  // Construction des boutons de créneau à partir des <option> du select
  picks.forEach((p) => {
    qa("option", p.select).filter((o) => o.value).forEach((o) => {
      const b = document.createElement("button");
      b.type = "button"; b.className = "slot"; b.dataset.h = o.value;
      b.innerHTML = `${o.value}<small>…</small>`;
      b.addEventListener("click", () => {
        p.select.value = p.select.value === o.value ? "" : o.value;
        p.select.dispatchEvent(new Event("change", { bubbles: true }));
      });
      p.box.appendChild(b); p.slots[o.value] = b;
    });
  });

  function refreshSlots() {
    const nb = Math.max(1, Number(nbInput.value) || 1);
    picks.forEach((p) => {
      Object.entries(p.slots).forEach(([h, b]) => {
        const left = p.cap - ((pris[p.id] || {})[h] || 0);
        const full = left < nb;
        b.disabled = full && p.select.value !== h;
        b.classList.toggle("on", p.select.value === h);
        b.classList.toggle("low", !full && left <= 5);
        b.setAttribute("aria-pressed", String(p.select.value === h));
        b.querySelector("small").textContent = left <= 0 ? "complet" : `${left} pl.`;
        b.title = left <= 0 ? "Créneau complet" : `${left} place(s) restante(s)`;
      });
      p.el.classList.toggle("has-slot", Boolean(p.select.value));
    });
  }

  function renderDay() {
    const nb = Math.max(1, Number(nbInput.value) || 1);
    const chosen = picks.filter((p) => p.select.value).sort((a, b) => toMin(a.select.value) - toMin(b.select.value));
    const list = q("#dayList"), total = q("#total");
    // Ne reconstruit la frise que si son contenu change : évite de remplacer un bouton en plein clic
    // (ex. : le « change » du champ « personnes » qui part au moment où l'on clique sur « retirer »).
    const sig = JSON.stringify([nb, chosen.map((p) => [p.id, p.select.value])]);
    if (sig !== lastSig) { lastSig = sig; buildList(); }
    function buildList() {
    if (!chosen.length) {
      list.innerHTML = `<li class="day-empty"><i class="fa-regular fa-calendar-plus"></i>Choisissez un créneau sur un jeu pour construire votre journée.</li>`;
    } else {
      list.innerHTML = "";
      chosen.forEach((p, i) => {
        const start = toMin(p.select.value), end = start + p.duree;
        const same = chosen.find((o, j) => j !== i && o.select.value === p.select.value);
        const li = document.createElement("li");
        li.className = "tl-item";
        li.innerHTML = `<span class="tl-time">${hhmm(start)} <em>→ ${hhmm(end)}</em></span>
          <span class="tl-price">${money(p.prix * nb)}</span>
          <span class="tl-name">${p.nom}<button type="button" class="tl-x" aria-label="Retirer ${p.nom}"><i class="fa-solid fa-xmark"></i></button></span>
          ${same ? `<span class="tl-note"><i class="fa-solid fa-circle-info"></i> Même horaire de départ que « ${same.nom} »</span>` : ""}`;
        li.querySelector(".tl-x").addEventListener("click", () => { p.select.value = ""; p.select.dispatchEvent(new Event("change", { bubbles: true })); });
        list.appendChild(li);
      });
    }
    }
    const sum = chosen.reduce((t, p) => t + p.prix * nb, 0);
    total.textContent = money(sum);
    q("#daySummary").textContent = chosen.length ? `${chosen.length} jeu${chosen.length > 1 ? "x" : ""} · ${chosen.reduce((t, p) => t + p.duree, 0)} min de jeu` : "Aucun jeu choisi";
    q("#dayPeople").textContent = `${nb} personne${nb > 1 ? "s" : ""}`;
    const d = dateInput.value ? new Date(dateInput.value + "T12:00:00") : null;
    q("#dayDate").textContent = d && !isNaN(d) ? d.toLocaleDateString("fr-FR", { weekday: "long", day: "numeric", month: "long" }) : "Choisissez une date";
    q("#daySubmit").disabled = !chosen.length;
  }

  async function loadAvailability() {
    if (!dateInput.value) return;
    picks.forEach((p) => p.box.classList.add("loading"));
    try {
      const r = await fetch(`${form.dataset.base}/client/disponibilites.php?date=${encodeURIComponent(dateInput.value)}`, { credentials: "same-origin", headers: { Accept: "application/json" } });
      if (!r.ok) throw new Error(r.status);
      pris = (await r.json()).pris || {};
      // Un créneau choisi peut être devenu complet pour ce groupe : on prévient et on le retire
      const nb = Math.max(1, Number(nbInput.value) || 1);
      picks.forEach((p) => {
        const h = p.select.value;
        if (h && p.cap - ((pris[p.id] || {})[h] || 0) < nb) {
          p.select.value = "";
          if (window.showToast) showToast(`« ${p.nom} » à ${h} n'a plus assez de places pour ${nb} personne(s).`, "info");
        }
      });
    } catch (_) {
      pris = {}; // en cas d'échec : le serveur vérifie de toute façon la capacité à la validation
    }
    picks.forEach((p) => p.box.classList.remove("loading"));
    refreshSlots(); renderDay();
  }

  const refresh = () => { refreshSlots(); renderDay(); };
  form.addEventListener("change", refresh);
  nbInput.addEventListener("input", refresh);
  dateInput.addEventListener("change", loadAvailability);
  refresh();
  loadAvailability();
})();
