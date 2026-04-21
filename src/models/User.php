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

    // ====================== МЕТОДЫ ПРОФИЛЯ ======================

    /**
     * Получить полные данные пользователя по ID
     */
    public function findById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("
SELECT
                id, name, last_name, email, phone, avatar,
                region, experience, rate, sphere,
                website, telegram, vk, about,
                created_at, updated_at
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Получить навыки пользователя
     */
    public function getSkills(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT skill_key FROM user_skills WHERE user_id = ?
        ");
        $stmt->execute([$userId]);

        $skills = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $skills[] = $row['skill_key'];
        }

        return $skills;
    }

    /**
     * Получить инструменты пользователя
     */
    public function getTools(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT tool_key FROM user_tools WHERE user_id = ?
        ");
        $stmt->execute([$userId]);

        $tools = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tools[] = $row['tool_key'];
        }

        return $tools;
    }

    /**
     * Получить работы портфолио пользователя
     */
    public function getPortfolio(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, image_path, created_at
            FROM portfolio_items
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);

        $portfolio = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $portfolio[] = [
                'id' => $row['id'],
                'image_path' => $row['image_path'],
                'created_at' => $row['created_at']
            ];
        }

        return $portfolio;
    }

    /**
     * Обновить данные профиля
     */
    public function updateProfile(int $userId, array $data): bool
    {
$allowedFields = [
            'name', 'last_name', 'region', 'experience', 'rate',
            'sphere', 'website', 'telegram', 'vk', 'about'
        ];

        $setParts = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $setParts[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        // Добавляем updated_at
        $setParts[] = "updated_at = NOW()";

        // Добавляем user_id в конец параметров
        $params[] = $userId;

        $sql = "UPDATE users SET " . implode(', ', $setParts) . " WHERE id = ?";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Обновить аватар
     */
    public function updateAvatar(int $userId, string $avatarPath): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users SET avatar = ?, updated_at = NOW() WHERE id = ?
        ");

        return $stmt->execute([$avatarPath, $userId]);
    }

    /**
     * Проверить уникальность навыков/инструментов (для валидации)
     */
    public function validateSkills(array $skills): array
    {
        // Список допустимых навыков из макета
        $validSkills = [
            'verstka', 'adaptive', 'animation', 'mobile', 'uxui',
            'webapps', 'landing', 'multipage', 'branding', 'logos'
        ];

        $invalid = [];
        foreach ($skills as $skill) {
            if (!in_array($skill, $validSkills)) {
                $invalid[] = $skill;
            }
        }

        return $invalid;
    }

    /**
     * Проверить уникальность инструментов (для валидации)
     */
    public function validateTools(array $tools): array
    {
        // Список допустимых инструментов из макета
        $validTools = [
            'figma', 'illustrator', 'photoshop', 'html', 'css', 'js',
            'react', 'vite', 'postgresql', 'cms', 'python', 'java',
            'cpp', 'csharp'
        ];

        $invalid = [];
        foreach ($tools as $tool) {
            if (!in_array($tool, $validTools)) {
                $invalid[] = $tool;
            }
        }

        return $invalid;
    }

    /**
     * Подсчитать количество работ в портфолио
     */
    public function countPortfolio(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM portfolio_items WHERE user_id = ?
        ");
        $stmt->execute([$userId]);

        return $stmt->fetchColumn();
    }
}
