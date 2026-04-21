<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../models/User.php';

$pdo = require __DIR__ . '/../../config/database.php';
$userModel = new User($pdo);

$errors = [];

// ====================== ОБРАБОТКА POST ЗАПРОСОВ ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Проверка CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $errors['general'] = 'Неверный токен безопасности. Попробуйте снова.';
        $_SESSION['form_errors'] = $errors;
        redirect('/profile.php');
    }

    // Определение действия
    $action = $_POST['action'] ?? 'update';

    switch ($action) {
        case 'update':
            updateProfile($pdo, $userModel, $errors);
            break;
        case 'upload_avatar':
            uploadAvatar($pdo, $userModel, $errors);
            break;
        case 'add_portfolio':
            addPortfolio($pdo, $userModel, $errors);
            break;
        case 'delete_portfolio':
            deletePortfolio($pdo, $userModel, $errors);
            break;
        default:
            updateProfile($pdo, $userModel, $errors);
    }
}

// ====================== AJAX ОБРАБОТКА ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];

    switch ($action) {
        case 'delete_portfolio_ajax':
            $response = deletePortfolioAjax($pdo, $userModel);
            break;
        case 'upload_portfolio_ajax':
            $response = uploadPortfolioAjax($pdo, $userModel);
            break;
    }

    echo json_encode($response);
    exit;
}

// ====================== ФУНКЦИИ ======================

/**
 * Обновление профиля пользователя
 */
function updateProfile(PDO $pdo, User $userModel, array &$errors): void
{
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        redirect('/login.php');
    }

    // Получение данных из POST
    $fullName = trim($_POST['full_name'] ?? '');
    $region = $_POST['region'] ?? '';
    $experience = $_POST['experience'] ?? '';
    $rate = $_POST['rate'] ?? '';
    $sphere = trim($_POST['sphere'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $telegram = trim($_POST['telegram'] ?? '');
    $vk = trim($_POST['vk'] ?? '');
    $about = trim($_POST['about'] ?? '');
    $skills = $_POST['skills'] ?? [];
    $tools = $_POST['tools'] ?? [];

    // Валидация
    if (empty($fullName) || mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
        $errors['full_name'] = 'Имя должно быть от 2 до 100 символов';
    }

    if (empty($region)) {
        $errors['region'] = 'Выберите регион';
    }

    if (empty($experience)) {
        $errors['experience'] = 'Выберите опыт работы';
    }

    if (empty($sphere)) {
        $errors['sphere'] = 'Укажите сферу деятельности';
    }

    if (!empty($rate) && (!is_numeric($rate) || $rate < 0)) {
        $errors['rate'] = 'Ставка должна быть положительным числом';
    }

    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors['website'] = 'Введите корректный URL';
    }

    if (!empty($telegram) && !preg_match('/^@?[a-zA-Z0-9_]{5,32}$/', $telegram)) {
        $errors['telegram'] = 'Некорректный формат Telegram';
    }

    if (!empty($about) && mb_strlen($about) > 1000) {
        $errors['about'] = 'Описание должно быть не более 1000 символов';
    }

    // Если есть ошибки - сохраняем в сессию и редиректим
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_old'] = $_POST;
        redirect('/profile.php');
    }

    // Разделение имени и фамилии
    $nameParts = explode(' ', $fullName, 2);
    $firstName = $nameParts[0] ?? '';
    $lastName = $nameParts[1] ?? '';

    try {
        // Обновление основных данных
        $stmt = $pdo->prepare("
            UPDATE users SET
                name = ?,
                last_name = ?,
                region = ?,
                experience = ?,
                rate = ?,
                sphere = ?,
                website = ?,
                telegram = ?,
                vk = ?,
                about = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $stmt->execute([
            $firstName,
            $lastName,
            $region,
            $experience,
            $rate ? (int)$rate : null,
            $sphere,
            $website ?: null,
            $telegram ?: null,
            $vk ?: null,
            $about ?: null,
            $userId
        ]);

        // Обновление навыков
        updateUserSkills($pdo, $userId, $skills);

        // Обновление инструментов
        updateUserTools($pdo, $userId, $tools);

        // Обновление имени в сессии
        $_SESSION['user_name'] = $firstName;

        set_flash('success', 'Профиль успешно обновлён!');
        redirect('/profile.php');

    } catch (PDOException $e) {
        error_log('Profile update error: ' . $e->getMessage());
        $errors['general'] = 'Произошла ошибка при обновлении профиля';
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_old'] = $_POST;
        redirect('/profile.php');
    }
}

/**
 * Загрузка аватара
 */
function uploadAvatar(PDO $pdo, User $userModel, array &$errors): void
{
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        redirect('/login.php');
    }

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors['avatar'] = 'Выберите файл для загрузки';
        $_SESSION['form_errors'] = $errors;
        redirect('/profile.php');
    }

    $file = $_FILES['avatar'];

    // Проверка ошибки загрузки
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors['avatar'] = 'Ошибка загрузки файла';
        $_SESSION['form_errors'] = $errors;
        redirect('/profile.php');
    }

    // Проверка типа файла
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        $errors['avatar'] = 'Допустимые форматы: JPG, PNG, WebP';
        $_SESSION['form_errors'] = $errors;
        redirect('/profile.php');
    }

    // Проверка размера (максимум 5 МБ)
    if ($file['size'] > 5 * 1024 * 1024) {
        $errors['avatar'] = 'Максимальный размер файла 5 МБ';
        $_SESSION['form_errors'] = $errors;
        redirect('/profile.php');
    }

    // Проверка что это изображение
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        $errors['avatar'] = 'Некорректное изображение';
        $_SESSION['form_errors'] = $errors;
        redirect('/profile.php');
    }

    // Создание директории если не существует
    $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Генерация имени файла
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'avatar_' . $userId . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $fileName;

    // Обработка и сохранение изображения (обрезка до 300x300)
    if (!processAndSaveImage($file['tmp_name'], $filePath, 300, 300)) {
        $errors['avatar'] = 'Ошибка обработки изображения';
        $_SESSION['form_errors'] = $errors;
        redirect('/profile.php');
    }

    // Получение старого аватара
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $oldAvatar = $stmt->fetchColumn();

    // Удаление старого аватара
    if ($oldAvatar && file_exists(__DIR__ . '/../../public' . $oldAvatar)) {
        unlink(__DIR__ . '/../../public' . $oldAvatar);
    }

    // Обновление в БД
    $avatarPath = '/uploads/avatars/' . $fileName;
    $stmt = $pdo->prepare("UPDATE users SET avatar = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$avatarPath, $userId]);

    set_flash('success', 'Аватар успешно обновлён!');
    redirect('/profile.php');
}

/**
 * Добавление работы в портфолио
 */
function addPortfolio(PDO $pdo, User $userModel, array &$errors): void
{
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        redirect('/login.php');
    }

    // Проверка количества работ (максимум 20)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM portfolio_items WHERE user_id = ?");
    $stmt->execute([$userId]);
    $count = $stmt->fetchColumn();

    if ($count >= 20) {
        $errors['portfolio'] = 'Максимум 20 работ в портфолио';
        $_SESSION['form_errors'] = $errors;
        redirect('/profile.php');
    }

    if (!isset($_FILES['portfolio']) || empty($_FILES['portfolio']['name'][0])) {
        $errors['portfolio'] = 'Выберите изображения';
        $_SESSION['form_errors'] = $errors;
        redirect('/profile.php');
    }

    $uploadDir = __DIR__ . '/../../public/uploads/portfolio/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $files = $_FILES['portfolio'];
    $uploaded = 0;

    foreach ($files['name'] as $index => $name) {
        if ($files['error'][$index] !== UPLOAD_ERR_OK) {
            continue;
        }

        // Проверка типа
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($files['tmp_name'][$index]);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mimeType, $allowedTypes)) {
            continue;
        }

        // Проверка размера (максимум 10 МБ)
        if ($files['size'][$index] > 10 * 1024 * 1024) {
            continue;
        }

        // Генерация имени файла
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $fileName = 'portfolio_' . $userId . '_' . time() . '_' . $index . '.' . $extension;
        $filePath = $uploadDir . $fileName;

        // Обработка и сохранение изображения (обрезка до 447x320)
        if (!processAndSaveImage($files['tmp_name'][$index], $filePath, 447, 320, true)) {
            continue;
        }

        // Сохранение в БД
        $stmt = $pdo->prepare("
            INSERT INTO portfolio_items (user_id, image_path, created_at)
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$userId, '/uploads/portfolio/' . $fileName]);
        $uploaded++;
    }

    if ($uploaded > 0) {
        set_flash('success', "Добавлено $uploaded работ в портфолио!");
    } else {
        $errors['portfolio'] = 'Не удалось загрузить изображения';
        $_SESSION['form_errors'] = $errors;
    }

    redirect('/profile.php');
}

/**
 * Удаление работы из портфолио
 */
function deletePortfolio(PDO $pdo, User $userModel, array &$errors): void
{
    $userId = $_SESSION['user_id'] ?? null;
    $itemId = $_POST['item_id'] ?? null;

    if (!$userId || !$itemId) {
        redirect('/profile.php');
    }

    // Получение информации о файле
    $stmt = $pdo->prepare("SELECT image_path FROM portfolio_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$itemId, $userId]);
    $item = $stmt->fetch();

    if (!$item) {
        $errors['portfolio'] = 'Работа не найдена';
        $_SESSION['form_errors'] = $errors;
        redirect('/profile.php');
    }

    // Удаление файла
    $filePath = __DIR__ . '/../../public' . $item['image_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Удаление из БД
    $stmt = $pdo->prepare("DELETE FROM portfolio_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$itemId, $userId]);

    set_flash('success', 'Работа удалена из портфолио!');
    redirect('/profile.php');
}

/**
 * AJAX удаление портфолио
 */
function deletePortfolioAjax(PDO $pdo, User $userModel): array
{
    $userId = $_SESSION['user_id'] ?? null;
    $itemId = $_POST['item_id'] ?? null;

    if (!$userId || !$itemId) {
        return ['success' => false, 'message' => 'Недостаточно данных'];
    }

    $stmt = $pdo->prepare("SELECT image_path FROM portfolio_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$itemId, $userId]);
    $item = $stmt->fetch();

    if (!$item) {
        return ['success' => false, 'message' => 'Работа не найдена'];
    }

    $filePath = __DIR__ . '/../../public' . $item['image_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    $stmt = $pdo->prepare("DELETE FROM portfolio_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$itemId, $userId]);

    return ['success' => true, 'message' => 'Работа удалена'];
}

/**
 * AJAX загрузка портфолио
 */
function uploadPortfolioAjax(PDO $pdo, User $userModel): array
{
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        return ['success' => false, 'message' => 'Не авторизован'];
    }

    // Проверка количества
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM portfolio_items WHERE user_id = ?");
    $stmt->execute([$userId]);
    if ($stmt->fetchColumn() >= 20) {
        return ['success' => false, 'message' => 'Максимум 20 работ'];
    }

    if (!isset($_FILES['file'])) {
        return ['success' => false, 'message' => 'Файл не загружен'];
    }

    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Ошибка загрузки'];
    }

    // Проверка типа
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Недопустимый формат'];
    }

    $uploadDir = __DIR__ . '/../../public/uploads/portfolio/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'portfolio_' . $userId . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $fileName;

    if (!processAndSaveImage($file['tmp_name'], $filePath, 447, 320, true)) {
        return ['success' => false, 'message' => 'Ошибка обработки'];
    }

    $stmt = $pdo->prepare("
        INSERT INTO portfolio_items (user_id, image_path, created_at)
        VALUES (?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$userId, '/uploads/portfolio/' . $fileName]);
    $itemId = $pdo->lastInsertId();

    return [
        'success' => true,
        'message' => 'Загружено',
        'id' => $itemId,
        'path' => '/uploads/portfolio/' . $fileName
    ];
}

/**
 * Обновление навыков пользователя
 */
function updateUserSkills(PDO $pdo, int $userId, array $skills): void
{
    // Удаляем старые навыки
    $stmt = $pdo->prepare("DELETE FROM user_skills WHERE user_id = ?");
    $stmt->execute([$userId]);

    if (empty($skills)) {
        return;
    }

    // Добавляем новые навыки
    $stmt = $pdo->prepare("INSERT INTO user_skills (user_id, skill_key) VALUES (?, ?)");
    foreach ($skills as $skill) {
        $stmt->execute([$userId, $skill]);
    }
}

/**
 * Обновление инструментов пользователя
 */
function updateUserTools(PDO $pdo, int $userId, array $tools): void
{
    // Удаляем старые инструменты
    $stmt = $pdo->prepare("DELETE FROM user_tools WHERE user_id = ?");
    $stmt->execute([$userId]);

    if (empty($tools)) {
        return;
    }

    // Добавляем новые инструменты
    $stmt = $pdo->prepare("INSERT INTO user_tools (user_id, tool_key) VALUES (?, ?)");
    foreach ($tools as $tool) {
        $stmt->execute([$userId, $tool]);
    }
}

/**
 * Обработка и сохранение изображения
 */
function processAndSaveImage(string $source, string $destination, int $width, int $height, bool $crop = false): bool
{
    $imageInfo = getimagesize($source);
    if (!$imageInfo) {
        return false;
    }

    [$origWidth, $origHeight, $type] = $imageInfo;

    // Создание исходного изображения
    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $srcImage = imagecreatefrompng($source);
            break;
        case IMAGETYPE_WEBP:
            $srcImage = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }

    if (!$srcImage) {
        return false;
    }

    // Создание нового изображения
    $dstImage = imagecreatetruecolor($width, $height);

    // Поддержка прозрачности для PNG
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        $transparent = imagecolorallocatealpha($dstImage, 0, 0, 0, 127);
        imagefill($dstImage, 0, 0, $transparent);
    }

    // Масштабирование с обрезкой или без
    if ($crop) {
        // Масштабирование с обрезкой (центрирование)
        $srcRatio = $origWidth / $origHeight;
        $dstRatio = $width / $height;

        if ($srcRatio > $dstRatio) {
            $newHeight = $origHeight;
            $newWidth = (int)($origHeight * $dstRatio);
            $srcX = (int)(($origWidth - $newWidth) / 2);
            $srcY = 0;
        } else {
            $newWidth = $origWidth;
            $newHeight = (int)($origWidth / $dstRatio);
            $srcX = 0;
            $srcY = (int)(($origHeight - $newHeight) / 2);
        }

        imagecopyresampled($dstImage, $srcImage, 0, 0, $srcX, $srcY, $width, $height, $newWidth, $newHeight);
    } else {
        // Простое масштабирование
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);
    }

    // Сохранение изображения
    $result = false;
    $extension = strtolower(pathinfo($destination, PATHINFO_EXTENSION));

    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $result = imagejpeg($dstImage, $destination, 90);
            break;
        case 'png':
            $result = imagepng($dstImage, $destination, 6);
            break;
        case 'webp':
            $result = imagewebp($dstImage, $destination, 90);
            break;
    }

    imagedestroy($srcImage);
    imagedestroy($dstImage);

    return $result;
}

// ====================== ОТОБРАЖЕНИЕ СТРАНИЦЫ ======================
// GET-запросы перенаправляются в profile.php для отображения
require_once __DIR__ . '/../../public/profile.php';
?>
