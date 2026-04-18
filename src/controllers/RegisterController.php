<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../models/User.php';

$pdo = require __DIR__ . '/../../config/database.php';
$userModel = new User($pdo);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim(strtolower($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm-password'] ?? '';

    // === Улучшенная валидация ===

    if (empty($name) || !preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $name)) {
        $errors['name'] = 'Имя может содержать только буквы';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 50) {
        $errors['name'] = 'Имя от 2 до 50 символов';
    }

    if (empty($phone) || !preg_match('/^(\+7|8)[0-9]{10}$/', preg_replace('/[^0-9+]/', '', $phone))) {
        $errors['phone'] = 'Введите корректный номер (+7 или 8 и 10 цифр)';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/\.(com|ru|net|org|io|co|info)$/i', $email)) {
        $errors['email'] = 'Введите корректный E-mail (например: user@example.com)';
    }

    if (empty($password) || strlen($password) < 8) {
        $errors['password'] = 'Пароль минимум 8 символов';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Пароль должен содержать буквы и цифры';
    }

    if ($password !== $confirm) {
        $errors['confirm-password'] = 'Пароли не совпадают';
    }

    // Проверка уникальности
    if (empty($errors['email']) && $userModel->emailExists($email)) {
        $errors['email'] = 'Пользователь с таким E-mail уже существует';
    }
    if (empty($errors['phone']) && $userModel->phoneExists($phone)) {
        $errors['phone'] = 'Пользователь с таким телефоном уже существует';
    }

    if (empty($errors)) {
        if ($userModel->register($name, $phone, $email, $password)) {
            set_flash('success', 'Регистрация прошла успешно!');
            unset($_SESSION['csrf_token']);
            redirect('/login.php');
        } else {
            $errors['general'] = 'Ошибка при сохранении в базу';
        }
    }

    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_old']    = $_POST;

    redirect('/registration.php');
}
