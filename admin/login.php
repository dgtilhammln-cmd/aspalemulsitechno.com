<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (adminIsAuthenticated()) {
    admin_redirect('dashboard');
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (adminAttemptLogin($username, $password)) {
        admin_redirect('dashboard');
    }

    $errorMessage = 'Username atau password salah. Coba lagi.';
}

/**
 * MUNG CMS V1
 * File: admin/login.php
 * Author: Firmanda Himawan
 * Description: Authentication entrypoint for the MUNG CMS administrative console.
 */

?><!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?= adminIdentityMeta() ?>
        <title>Login Admin | Aspal Emulsi</title>
        <link rel="stylesheet" href="admin.css">
    </head>
    <body class="auth-page">
        <div class="auth-wrapper">
            <div class="auth-card">
                <div class="auth-card__header">
                    <div class="auth-card__logo">
                        <?php
                            $siteSettings = adminFetchSiteSettings();
                            $logoUrl = $siteSettings['logo_url'] ?? null;
                            if ($logoUrl === null || $logoUrl === '') {
                                $logoUrl = '/images/logo.png';
                            }
                        ?>
                        <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="<?= htmlspecialchars($siteSettings['company_name'] ?? 'Aspal Emulsi', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                    </div>
                    <span class="auth-card__subtitle">ASPAL EMULSI TECHNO.COM</span>
                </div>
                <h2>Login Admin</h2>
                <form method="post" class="auth-form" autocomplete="off">
                    <div class="form-field">
                        <label for="username">Username</label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="admin"
                            required
                            value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES) ?>"
                        >
                    </div>
                    <div class="form-field form-field--password">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            required
                        >
                        <button type="button" class="toggle-password" data-toggle-password aria-label="Tampilkan password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                    <div class="auth-card__footer">
                        <a class="auth-link" href="<?= admin_url('forgot-password') ?>">Lupa kata sandi?</a>
                        <button type="submit" class="btn btn-primary btn-login">Masuk</button>
                    </div>
                </form>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <?php if ($errorMessage !== ''): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Login gagal',
                    text: <?= json_encode($errorMessage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                    confirmButtonColor: '#ef4444'
                });
            </script>
        <?php endif; ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const passwordInput = document.getElementById('password');
                const toggleButton = document.querySelector('[data-toggle-password]');

                if (!passwordInput || !toggleButton) {
                    return;
                }

                let isVisible = false;
                const icons = {
                    eye: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" /><circle cx="12" cy="12" r="3" /></svg>',
                    eyeOff: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.77 21.77 0 0 1 5.06-5.94" /><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" /><path d="m1 1 22 22" /></svg>'
                };

                const updateToggle = () => {
                    toggleButton.innerHTML = isVisible ? icons.eyeOff : icons.eye;
                    toggleButton.setAttribute('aria-label', isVisible ? 'Sembunyikan password' : 'Tampilkan password');
                };

                toggleButton.addEventListener('click', () => {
                    isVisible = !isVisible;
                    passwordInput.type = isVisible ? 'text' : 'password';
                    updateToggle();
                });

                updateToggle();
            });
        </script>
    </body>
</html>


