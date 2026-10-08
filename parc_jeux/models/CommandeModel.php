<?php
/** Commandes et billets. */
class CommandeModel {
    public function __construct(private PDO $pdo) {}

    public function forClient(int $uid): array {
        $st = $this->pdo->prepare("SELECT * FROM commande WHERE id_client = ? ORDER BY date_commande DESC");
        $st->execute([$uid]);
        return $st->fetchAll();
    }

    /** Billets groupés par id de commande. */
    public function billetsPour(array $ids): array {
        $out = [];
        if (!$ids) return $out;
        $in = implode(',', array_fill(0, count($ids), '?'));
        $q = $this->pdo->prepare("SELECT * FROM billet WHERE id_commande IN ($in) ORDER BY id_billet");
        $q->execute(array_values($ids));
        foreach ($q->fetchAll() as $b) $out[$b['id_commande']][] = $b;
        return $out;
    }

    public function countBillets(?int $uid = null): int {
        if ($uid === null) return (int)$this->pdo->query("SELECT COUNT(*) FROM billet")->fetchColumn();
        $q = $this->pdo->prepare("SELECT COUNT(*) FROM billet b JOIN commande c ON c.id_commande = b.id_commande WHERE c.id_client = ?");
        $q->execute([$uid]);
        return (int)$q->fetchColumn();
    }

    /** Achat de billets (paiement par carte simulé : statut payé directement). */
    public function acheterBillets(int $uid, string $type, array $ticket, int $qte, string $date): void {
        $total = $ticket['prix'] * $qte;
        $validite = $ticket['validite_jours'] > 0
            ? date('Y-m-d', strtotime($date . ' +' . $ticket['validite_jours'] . ' days'))
            : $date;
        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare("INSERT INTO commande (num_commande, montant_total, mode_paiement, statut_paiement, id_client) VALUES (?,?, 'carte', 'paye', ?)")
                ->execute([genCode('CMD'), $total, $uid]);
            $idCmd = (int)$this->pdo->lastInsertId();
            $ib = $this->pdo->prepare("INSERT INTO billet (code_unique, type, prix_applique, date_validite, id_commande) VALUES (?,?,?,?,?)");
            for ($i = 0; $i < $qte; $i++) $ib->execute([genCode('BIL', 5), $type, $ticket['prix'], $validite, $idCmd]);
            $this->pdo->prepare("INSERT INTO recette (source, montant, id_commande) VALUES (?, ?, ?)")
                ->execute([$type === 'abonnement' ? 'abonnement' : 'billet', $total, $idCmd]);
            $this->pdo->commit();
        } catch (Throwable $ex) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $ex;
        }
    }
}
