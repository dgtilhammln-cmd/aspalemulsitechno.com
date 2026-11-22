<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (adminIsAuthenticated()) {
    admin_redirect('dashboard');
}

adminEnsurePasswordResetTable();

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$email = trim((string) ($_GET['email'] ?? $_POST['email'] ?? ''));

if ($token !== '' && !preg_match('/^[A-Fa-f0-9]{64}$/', $token)) {
    $token = '';
}

$errors = [];
$successMessage = '';
$showForm = true;

if ($token === '' || $email === '') {
    $errors[] = 'Tautan reset tidak valid atau sudah tidak berlaku.';
    $showForm = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errors === []) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['password_confirmation'] ?? '';

    if ($password === '' || $confirmPassword === '') {
        $errors[] = 'Password baru dan konfirmasi wajib diisi.';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter.';
    }

    if ($errors === []) {
        $user = adminFetchUserByEmail($email);

        if ($user === null) {
            $errors[] = 'Tautan reset tidak valid atau sudah digunakan.';
        } else {
            $tokenRow = adminValidatePasswordResetToken((int) $user['id'], $token);

            if ($tokenRow === null) {
                $errors[] = 'Tautan reset tidak valid atau sudah kedaluwarsa.';
            } else {
                try {
                    $pdo = getPDO();
                    $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id')->execute([
                        'hash' => password_hash($password, PASSWORD_DEFAULT),
                        'id' => (int) $user['id'],
                    ]);
                    adminDeletePasswordResetTokens((int) $user['id']);
                    $successMessage = 'Password berhasil diperbarui. Silakan login dengan password baru.';
                    $showForm = false;
                } catch (Throwable $throwable) {
                    $errors[] = 'Gagal memperbarui password. Coba lagi.';
                    error_log('Update password via reset failed: ' . $throwable->getMessage());
                }
            }
        }
    }
}

/**
 * MUNG CMS V1
 * File: admin/reset-password.php
 * Author: Firmanda Himawan
 * Description: Secure token-based password reset form for administrators.
 */
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Reset Password | Aspal Emulsi</title>
        <link rel="stylesheet" href="admin.css">
    </head>
    <body class="auth-page">
        <div class="auth-wrapper">
            <div class="auth-card">
                <div class="auth-brand">
                    <span class="logo-circle"><img src="/favicon.png" alt="Aspal Emulsi"></span>
                    <div>
                        <h1>Aspal Emulsi</h1>
                        <p>Reset password admin</p>
                    </div>
                </div>
                <h2>Reset Password</h2>

                <?php if ($successMessage !== ''): ?>
                    <div class="alert alert-success" role="status">
                        <?= htmlspecialchars($successMessage, ENT_QUOTES) ?>
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

                <?php if ($showForm): ?>
                    <form method="post" class="auth-form" autocomplete="off">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES) ?>">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES) ?>">
                        <div class="form-field">
                            <label for="password">Password baru</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Minimal 8 karakter"
                                required
                            >
                        </div>
                        <div class="form-field">
                            <label for="password_confirmation">Konfirmasi password</label>
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                placeholder="Ulangi password baru"
                                required
                            >
                        </div>
                        <button type="submit" class="btn btn-primary">Perbarui password</button>
                    </form>
                <?php endif; ?>

                <p class="auth-hint">
                    Kembali ke halaman <a class="auth-link" href="<?= admin_url('login') ?>">login</a>.
                </p>
            </div>
        </div>
    </body>
</html>
