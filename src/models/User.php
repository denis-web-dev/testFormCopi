<?php
declare(strict_types=1);

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }


    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }

    public function phoneExists(string $phone): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        return $stmt->rowCount() > 0;
    }

    public function register(string $name, string $phone, string $email, string $password): bool
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, phone, email, password)
            VALUES (?, ?, ?, ?)
        ");

        return $stmt->execute([$name, $phone, $email, $hashedPassword]);
    }


    public function findByLogin(string $login): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM users
            WHERE email = ? OR phone = ?
            LIMIT 1
        ");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        return $user ?: null;
    }
}
