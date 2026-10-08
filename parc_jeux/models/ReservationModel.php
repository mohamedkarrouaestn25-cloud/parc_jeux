<?php
/** Réservations de jeux : lecture, création (avec contrôle de capacité), annulation, paiement. */
class ReservationModel {
    public function __construct(private PDO $pdo) {}

    public function forClient(int $uid): array {
        $st = $this->pdo->prepare("SELECT * FROM reservation WHERE id_client = ? ORDER BY date_creation DESC");
        $st->execute([$uid]);
        return $st->fetchAll();
    }

    public function findForClient(int $id, int $uid): ?array {
        $st = $this->pdo->prepare("SELECT * FROM reservation WHERE id_reservation = ? AND id_client = ?");
        $st->execute([$id, $uid]);
        return $st->fetch() ?: null;
    }

    /** Lignes (jeu + créneau) groupées par id de réservation. */
    public function lignesPour(array $ids): array {
        $out = [];
        if (!$ids) return $out;
        $in = implode(',', array_fill(0, count($ids), '?'));
        $q = $this->pdo->prepare("SELECT cj.*, j.nom FROM concerner cj JOIN jeu j ON j.id_jeu = cj.id_jeu WHERE cj.id_reservation IN ($in) ORDER BY cj.creneau");
        $q->execute(array_values($ids));
        foreach ($q->fetchAll() as $l) $out[$l['id_reservation']][] = $l;
        return $out;
    }

    public function countActives(?int $uid = null): int {
        if ($uid === null) return (int)$this->pdo->query("SELECT COUNT(*) FROM reservation WHERE statut <> 'annulee'")->fetchColumn();
        $q = $this->pdo->prepare("SELECT COUNT(*) FROM reservation WHERE id_client = ? AND statut <> 'annulee'");
        $q->execute([$uid]);
        return (int)$q->fetchColumn();
    }

    /** Prochaine visite à venir (confirmée ou payée) avec ses jeux. */
    public function prochaineVisite(int $uid): ?array {
        $q = $this->pdo->prepare("SELECT * FROM reservation WHERE id_client = ? AND statut IN ('confirmee','payee') AND date_visite >= CURDATE() ORDER BY date_visite, id_reservation LIMIT 1");
        $q->execute([$uid]);
        $r = $q->fetch();
        if (!$r) return null;
        $r['lignes'] = $this->lignesPour([$r['id_reservation']])[$r['id_reservation']] ?? [];
        return $r;
    }

    /** Règle d'annulation : confirmée/en attente ET visite postérieure à aujourd'hui. */
    public static function peutAnnuler(array $r): bool {
        return in_array($r['statut'], ['confirmee', 'en_attente'], true) && $r['date_visite'] > date('Y-m-d');
    }

    public function annuler(int $id): void {
        $this->pdo->prepare("UPDATE reservation SET statut = 'annulee' WHERE id_reservation = ?")->execute([$id]);
    }

    /** Paiement (simulation) : crée la commande et la recette, passe la réservation à « payée ». */
    public function payer(array $r, int $uid): void {
        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare("INSERT INTO commande (num_commande, montant_total, mode_paiement, statut_paiement, id_client) VALUES (?,?, 'carte', 'paye', ?)")
                ->execute([genCode('CMD'), $r['montant'], $uid]);
            $idCmd = (int)$this->pdo->lastInsertId();
            $this->pdo->prepare("UPDATE reservation SET statut = 'payee', id_commande = ? WHERE id_reservation = ?")->execute([$idCmd, $r['id_reservation']]);
            $this->pdo->prepare("INSERT INTO recette (source, montant, id_commande) VALUES ('reservation', ?, ?)")->execute([$r['montant'], $idCmd]);
            $this->pdo->commit();
        } catch (Throwable $ex) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $ex;
        }
    }

    /**
     * Crée une réservation. $choix = [id_jeu => 'HH:MM'].
     * Lève RuntimeException (message affichable) si un jeu n'est plus dispo ou si la capacité est dépassée.
     */
    public function creer(int $uid, string $date, int $nb, array $choix): int {
        try {
            $this->pdo->beginTransaction();
            $montant = 0;
            $lignes = [];
            foreach ($choix as $id => $c) {
                // Verrou sur le jeu : empêche deux réservations simultanées de dépasser la capacité
                $st = $this->pdo->prepare("SELECT nom, capacite, tarif, etat FROM jeu WHERE id_jeu = ? FOR UPDATE");
                $st->execute([$id]);
                $j = $st->fetch();
                if (!$j || !in_array($j['etat'], JeuModel::ETATS_RESERVABLES, true)) {
                    throw new RuntimeException("Le jeu « " . ($j['nom'] ?? '?') . " » n'est plus disponible.");
                }
                $cr = $date . ' ' . $c . ':00';
                $q = $this->pdo->prepare("SELECT COALESCE(SUM(cj.nb_personnes),0) FROM concerner cj
                                          JOIN reservation r ON r.id_reservation = cj.id_reservation
                                          WHERE cj.id_jeu = ? AND cj.creneau = ? AND r.statut <> 'annulee'");
                $q->execute([$id, $cr]);
                $pris = (int)$q->fetchColumn();
                if ($pris + $nb > (int)$j['capacite']) {
                    throw new RuntimeException("« {$j['nom']} » à $c : il reste " . max(0, $j['capacite'] - $pris) . " place(s) seulement.");
                }
                $sous = $j['tarif'] * $nb;
                $montant += $sous;
                $lignes[] = [$id, $cr, $sous];
            }
            $this->pdo->prepare("INSERT INTO reservation (numero, date_visite, nb_personnes, montant, statut, id_client) VALUES (?,?,?,?, 'confirmee', ?)")
                ->execute([genCode('RES'), $date, $nb, $montant, $uid]);
            $idRes = (int)$this->pdo->lastInsertId();
            $li = $this->pdo->prepare("INSERT INTO concerner (id_reservation, id_jeu, creneau, nb_personnes, sous_total) VALUES (?,?,?,?,?)");
            foreach ($lignes as [$id, $cr, $sous]) $li->execute([$idRes, $id, $cr, $nb, $sous]);
            $this->pdo->commit();
            return $idRes;
        } catch (Throwable $ex) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $ex;
        }
    }

    /** Places déjà prises un jour donné : [id_jeu => ['10:00' => 12, ...]] (réservations non annulées). */
    public function placesPrises(string $date): array {
        $q = $this->pdo->prepare("SELECT cj.id_jeu, DATE_FORMAT(cj.creneau, '%H:%i') AS h, SUM(cj.nb_personnes) AS pris
                                  FROM concerner cj JOIN reservation r ON r.id_reservation = cj.id_reservation
                                  WHERE DATE(cj.creneau) = ? AND r.statut <> 'annulee' GROUP BY cj.id_jeu, h");
        $q->execute([$date]);
        $out = [];
        foreach ($q->fetchAll() as $l) $out[(int)$l['id_jeu']][$l['h']] = (int)$l['pris'];
        return $out;
    }
}
