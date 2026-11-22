<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (adminIsAuthenticated()) {
    admin_redirect('dashboard');
}

adminEnsurePasswordResetTable();

$errors = [];
$successMessage = '';
$emailValue = '';
$mailDeliveryFailed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = trim($_POST['email'] ?? '');

    if ($emailValue === '') {
        $errors[] = 'Email wajib diisi.';
    } elseif (!filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    if ($errors === []) {
        $user = adminFetchUserByEmail($emailValue);

        if ($user !== null) {
            $token = adminCreatePasswordResetToken((int) $user['id']);

            if ($token === null) {
                $errors[] = 'Terjadi kesalahan saat membuat tautan reset. Coba lagi.';
            } else {
                $mailDeliveryFailed = !adminSendPasswordResetEmail($emailValue, $token);
            }
        }

        if ($errors === []) {
            $successMessage = 'Jika email terdaftar, tautan reset sudah kami kirim. Periksa inbox maupun folder spam Anda.';
        }
    }
}

/**
 * MUNG CMS V1
 * File: admin/forgot-password.php
 * Author: Firmanda Himawan
 * Description: Password recovery request interface for admin users.
 */
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Lupa Password | Aspal Emulsi</title>
        <link rel="stylesheet" href="admin.css">
    </head>
    <body class="auth-page">
        <div class="auth-wrapper">
            <div class="auth-card">
                <div class="auth-brand">
                    <span class="logo-circle">AE</span>
                    <div>
                        <h1>Aspal Emulsi</h1>
                        <p>Reset password admin</p>
                    </div>
                </div>
                <h2>Lupa Password</h2>

                <?php if ($successMessage !== ''): ?>
                    <div class="alert alert-success" role="status">
                        <?= htmlspecialchars($successMessage, ENT_QUOTES) ?>
                        <?php if ($mailDeliveryFailed): ?>
                            <br><small>Email reset tidak dapat dikirim otomatis. Silakan hubungi super admin untuk bantuan lebih lanjut.</small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($errors !== []): ?>
                    <div class="alert alert-error" role="alert">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" class="auth-form" autocomplete="off">
                    <div class="form-field">
                        <label for="email">Email terdaftar</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="admin@aspalemulsi.local"
                            value="<?= htmlspecialchars($emailValue, ENT_QUOTES) ?>"
                            required
                        >
                    </div>
                    <button type="submit" class="btn btn-primary">Kirim tautan reset</button>
                </form>
                <p class="auth-hint">
                    Ingat password? <a class="auth-link" href="<?= admin_url('login') ?>">Kembali ke halaman login</a>.
                </p>
            </div>
        </div>
    </body>
</html>
