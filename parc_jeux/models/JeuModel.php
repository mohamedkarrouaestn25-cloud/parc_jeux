<?php
/** Accès aux données de la table `jeu`. */
class JeuModel {
    public const ETATS_RESERVABLES = ['disponible', 'en_fonctionnement'];

    public function __construct(private PDO $pdo) {}

    public function all(): array {
        return $this->pdo->query("SELECT * FROM jeu ORDER BY id_jeu")->fetchAll();
    }

    public function find(int $id): ?array {
        $st = $this->pdo->prepare("SELECT * FROM jeu WHERE id_jeu = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function reservables(): array {
        return $this->pdo->query("SELECT * FROM jeu WHERE etat IN ('disponible','en_fonctionnement') ORDER BY nom")->fetchAll();
    }

    public static function estReservable(array $jeu): bool {
        return in_array($jeu['etat'], self::ETATS_RESERVABLES, true);
    }

    public function count(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM jeu")->fetchColumn();
    }
}
