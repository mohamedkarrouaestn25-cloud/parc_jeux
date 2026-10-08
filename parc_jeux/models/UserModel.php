<?php
/** Accès aux données de la table `utilisateur`. */
class UserModel {
    public function __construct(private PDO $pdo) {}

    public function findByEmail(string $email): ?array {
        $st = $this->pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
        $st->execute([$email]);
        return $st->fetch() ?: null;
    }

    public function emailExists(string $email): bool {
        $st = $this->pdo->prepare("SELECT 1 FROM utilisateur WHERE email = ?");
        $st->execute([$email]);
        return (bool)$st->fetch();
    }

    public function createClient(array $v, string $password): void {
        $this->pdo->prepare("INSERT INTO utilisateur (nom, prenom, email, telephone, mot_de_passe, role) VALUES (?,?,?,?,?, 'client')")
            ->execute([$v['nom'], $v['prenom'], $v['email'], $v['telephone'], password_hash($password, PASSWORD_DEFAULT)]);
    }
}
