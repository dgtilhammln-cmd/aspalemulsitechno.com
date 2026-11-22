<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

adminRequireRole('superadmin');

$pdo = getPDO();
$currentUser = adminCurrentUser();

$statuses = ['active', 'inactive', 'suspended'];

$rolesStmt = $pdo->query(
    'SELECT id, name, slug, description
     FROM roles
     ORDER BY name ASC'
);
$roles = $rolesStmt->fetchAll() ?: [];
$roleMap = [];
foreach ($roles as $roleRow) {
    $roleMap[(int) $roleRow['id']] = $roleRow;
}

$errors = [];
$notices = [];

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: null;
$mode = $editId ? 'edit' : 'create';

$formData = [
    'username' => '',
    'email' => '',
    'full_name' => '',
    'role_id' => null,
    'status' => 'active',
];

if ($mode === 'edit') {
    $userStmt = $pdo->prepare(
        'SELECT id, username, email, full_name, role_id, status
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $userStmt->execute(['id' => $editId]);
    $userRow = $userStmt->fetch();

    if ($userRow === false) {
        $mode = 'create';
        $editId = null;
        $notices[] = ['type' => 'warning', 'message' => 'User tidak ditemukan.'];
    } else {
        $formData = [
            'username' => (string) $userRow['username'],
            'email' => (string) ($userRow['email'] ?? ''),
            'full_name' => (string) ($userRow['full_name'] ?? ''),
            'role_id' => $userRow['role_id'] !== null ? (int) $userRow['role_id'] : null,
            'status' => (string) $userRow['status'],
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    $requestId = isset($_POST['id']) ? (int) $_POST['id'] : null;

    if ($action === 'delete') {
        if ($requestId === null) {
            $errors[] = 'ID user tidak valid.';
        } elseif ($currentUser !== null && $currentUser['id'] === $requestId) {
            $errors[] = 'Tidak bisa menghapus akun yang sedang digunakan.';
        } else {
            try {
                $pdo->beginTransaction();

                adminDeletePasswordResetTokens($requestId);

                $deleteStmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
                $deleteStmt->execute(['id' => $requestId]);

                $pdo->commit();

                admin_redirect('users', ['notice' => 'deleted']);
            } catch (Throwable $throwable) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Gagal menghapus user: ' . $throwable->getMessage();
            }
        }
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $roleId = isset($_POST['role_id']) && $_POST['role_id'] !== '' ? (int) $_POST['role_id'] : null;
        $status = trim($_POST['status'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $formData = [
            'username' => $username,
            'email' => $email,
            'full_name' => $fullName,
            'role_id' => $roleId,
            'status' => $status !== '' ? $status : 'active',
        ];

        if ($username === '') {
            $errors[] = 'Username wajib diisi.';
        } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            $errors[] = 'Username hanya boleh huruf, angka, titik, strip, underscore (3-50 karakter).';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }

        if ($roleId !== null && !isset($roleMap[$roleId])) {
            $errors[] = 'Role tidak dikenal.';
        }

        if ($status === '' || !in_array($status, $statuses, true)) {
            $errors[] = 'Status user tidak valid.';
        }

        $isEdit = $action === 'update' && $requestId !== null;

        if (!$isEdit && $password === '') {
            $errors[] = 'Password wajib diisi.';
        }

        if ($password !== '' && strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Konfirmasi password tidak cocok.';
        }

        try {
            if ($errors === []) {
                if ($isEdit) {
                    $dupStmt = $pdo->prepare(
                        'SELECT COUNT(*) FROM users WHERE (username = :username OR email = :email) AND id <> :id LIMIT 1'
                    );
                    $dupStmt->execute([
                        'username' => $username,
                        'email' => $email !== '' ? $email : null,
                        'id' => $requestId,
                    ]);
                } else {
                    $dupStmt = $pdo->prepare(
                        'SELECT COUNT(*) FROM users WHERE username = :username OR email = :email LIMIT 1'
                    );
                    $dupStmt->execute([
                        'username' => $username,
                        'email' => $email !== '' ? $email : null,
                    ]);
                }

                if ((int) $dupStmt->fetchColumn() > 0) {
                    $errors[] = 'Username atau email sudah digunakan.';
                }
            }

            if ($errors === []) {
                $pdo->beginTransaction();

                if ($isEdit) {
                    $updateFields = [
                        'username' => $username,
                        'email' => $email !== '' ? $email : null,
                        'full_name' => $fullName !== '' ? $fullName : null,
                        'role_id' => $roleId,
                        'status' => $status,
                        'id' => $requestId,
                    ];

                    $updateSql = 'UPDATE users
                        SET username = :username,
                            email = :email,
                            full_name = :full_name,
                            role_id = :role_id,
                            status = :status';

                    if ($password !== '') {
                        $updateSql .= ', password_hash = :password_hash';
                        $updateFields['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                        adminDeletePasswordResetTokens($requestId);
                    }

                    $updateSql .= ', updated_at = CURRENT_TIMESTAMP WHERE id = :id';

                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->execute($updateFields);

                    $pdo->commit();
                    admin_redirect('users', ['notice' => 'updated']);
                } else {
                    $insertStmt = $pdo->prepare(
                        'INSERT INTO users (role_id, username, email, full_name, password_hash, status)
                         VALUES (:role_id, :username, :email, :full_name, :password_hash, :status)'
                    );
                    $insertStmt->execute([
                        'role_id' => $roleId,
                        'username' => $username,
                        'email' => $email !== '' ? $email : null,
                        'full_name' => $fullName !== '' ? $fullName : null,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'status' => $status,
                    ]);

                    $pdo->commit();
                    admin_redirect('users', ['notice' => 'created']);
                }
            }
        } catch (Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Terjadi kesalahan: ' . $throwable->getMessage();
        }
    }
}

if (isset($_GET['notice'])) {
    $noticeKey = $_GET['notice'];
    $messages = [
        'created' => 'User berhasil dibuat.',
        'updated' => 'User berhasil diperbarui.',
        'deleted' => 'User berhasil dihapus.',
    ];

    if (isset($messages[$noticeKey])) {
        $notices[] = ['type' => 'success', 'message' => $messages[$noticeKey]];
    }
}

$usersStmt = $pdo->query(
    'SELECT u.id, u.username, u.email, u.full_name, u.status, u.created_at, u.last_login_at,
            r.name AS role_name, r.slug AS role_slug
     FROM users u
     LEFT JOIN roles r ON r.id = u.role_id
     ORDER BY u.created_at DESC'
);
$users = $usersStmt->fetchAll() ?: [];

$userStats = [
    'active' => 0,
    'inactive' => 0,
    'suspended' => 0,
    'with_email' => 0,
    'latest_login' => null,
];

foreach ($users as $userRow) {
    $status = (string) ($userRow['status'] ?? '');
    if (array_key_exists($status, $userStats)) {
        $userStats[$status]++;
    }
    if (!empty($userRow['email'])) {
        $userStats['with_email']++;
    }
    if (!empty($userRow['last_login_at'])) {
        try {
            $loginAt = new DateTime($userRow['last_login_at']);
            if ($userStats['latest_login'] === null || $loginAt > $userStats['latest_login']) {
                $userStats['latest_login'] = $loginAt;
            }
        } catch (Throwable $ignored) {
            // ignore parse error
        }
    }
}

$totalUsers = count($users);
$latestLoginLabel = $userStats['latest_login'] instanceof DateTime
    ? $userStats['latest_login']->format('d M Y H:i')
    : '—';

?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Manajemen User | Aspal Emulsi</title>
        <link rel="stylesheet" href="admin.css">
        <script src="app.js" defer></script>
    </head>
    <body class="dashboard">
        <?php
            $sidebarActive = 'users';
            require __DIR__ . '/partials/sidebar.php';
        ?>
        <main class="content">
            <?php
                adminRenderTopbar([
                    'brand' => 'Aspal Emulsi',
                    'subtitle' => 'Admin Intelligence',
                    'pageTitle' => 'Kelola User Admin',
                    'pageDescription' => 'Halo ' . htmlspecialchars($currentUser['name'] ?? $currentUser['username'], ENT_QUOTES) . ', atur akses admin di sini.',
                    'breadcrumbs' => [
                        ['label' => 'Dashboard', 'href' => admin_url('dashboard')],
                        ['label' => 'Pengaturan'],
                        ['label' => 'User'],
                    ],
                    'quickActions' => [
                        [
                            'label' => 'Tambah User',
                            'href' => admin_url('users'),
                            'variant' => 'primary',
                        ],
                        [
                            'label' => 'Pengaturan Umum',
                            'href' => admin_url('settings'),
                            'variant' => 'ghost',
                        ],
                    ],
                ]);
            ?>
            <div class="workspace-content">
                <section class="workspace-meta">
                    <span class="status-pill">Total User: <?= number_format($totalUsers) ?></span>
                    <span class="chip chip--success">Aktif <?= number_format($userStats['active']) ?></span>
                    <span class="chip chip--warning">Nonaktif <?= number_format($userStats['inactive']) ?></span>
                    <span class="chip chip--neutral">Email Tersimpan <?= number_format($userStats['with_email']) ?></span>
                    <span class="chip chip--default">Login Terbaru <?= htmlspecialchars($latestLoginLabel, ENT_QUOTES) ?></span>
                </section>

                <section class="card">
                <header>
                    <h2><?= $mode === 'edit' ? 'Edit User' : 'Tambah User' ?></h2>
                    <?php if ($mode === 'edit'): ?>
                        <a class="btn btn-outline" href="<?= admin_url('users') ?>">Batal</a>
                    <?php endif; ?>
                </header>
                <form method="post" class="form-grid" autocomplete="off">
                    <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update' : 'create' ?>">
                    <?php if ($mode === 'edit' && $editId !== null): ?>
                        <input type="hidden" name="id" value="<?= (int) $editId ?>">
                    <?php endif; ?>
                    <label class="form-field">
                        <span>Username</span>
                        <input type="text" name="username" required value="<?= htmlspecialchars($formData['username'], ENT_QUOTES) ?>" placeholder="superadmin">
                    </label>
                    <label class="form-field">
                        <span>Nama Lengkap</span>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($formData['full_name'], ENT_QUOTES) ?>" placeholder="Nama lengkap (opsional)">
                    </label>
                    <label class="form-field full">
                        <span>Email</span>
                        <input type="email" name="email" value="<?= htmlspecialchars($formData['email'], ENT_QUOTES) ?>" placeholder="email@domain.com (opsional)">
                    </label>
                    <label class="form-field">
                        <span>Role</span>
                        <select name="role_id">
                            <option value="">Tanpa Role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= (int) $role['id'] ?>" <?= $formData['role_id'] === (int) $role['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role['name'], ENT_QUOTES) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="form-field">
                        <span>Status</span>
                        <select name="status" required>
                            <?php foreach ($statuses as $statusOption): ?>
                                <option value="<?= htmlspecialchars($statusOption, ENT_QUOTES) ?>" <?= $formData['status'] === $statusOption ? 'selected' : '' ?>>
                                    <?= ucfirst($statusOption) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="form-field">
                        <span>Password <?= $mode === 'edit' ? '(kosongkan jika tidak ganti)' : '' ?></span>
                        <input type="password" name="password" <?= $mode === 'edit' ? '' : 'required' ?> minlength="8" placeholder="Minimal 8 karakter">
                    </label>
                    <label class="form-field">
                        <span>Konfirmasi Password</span>
                        <input type="password" name="password_confirm" <?= $mode === 'edit' ? '' : 'required' ?> minlength="8" placeholder="Ulangi password">
                    </label>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?= $mode === 'edit' ? 'Simpan Perubahan' : 'Tambah User' ?>
                        </button>
                    </div>
                </form>
            </section>

            <section class="card table-card">
                <header>
                    <h2>Daftar User</h2>
                </header>
                <div class="table-responsive table-responsive--flush">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Login Terakhir</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users === []): ?>
                                <tr>
                                    <td colspan="8">Belum ada user.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $userRow): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($userRow['username'], ENT_QUOTES) ?></td>
                                        <td><?= htmlspecialchars($userRow['full_name'] ?? '—', ENT_QUOTES) ?></td>
                                        <td><?= htmlspecialchars($userRow['email'] ?? '—', ENT_QUOTES) ?></td>
                                        <td>
                                            <?= htmlspecialchars($userRow['role_name'] ?? '—', ENT_QUOTES) ?>
                                            <?php if (!empty($userRow['role_slug'])): ?>
                                                <small>(<?= htmlspecialchars($userRow['role_slug'], ENT_QUOTES) ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($userRow['status'] === 'active'): ?>
                                                <span class="badge badge-success">Aktif</span>
                                            <?php elseif ($userRow['status'] === 'inactive'): ?>
                                                <span class="badge badge-warning">Nonaktif</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Suspended</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $lastLogin = '—';
                                            if (!empty($userRow['last_login_at'])) {
                                                try {
                                                    $lastLogin = (new DateTime($userRow['last_login_at']))->format('d M Y H:i');
                                                } catch (Throwable $ignored) {
                                                    $lastLogin = (string) $userRow['last_login_at'];
                                                }
                                            }
                                            ?>
                                            <?= htmlspecialchars($lastLogin, ENT_QUOTES) ?>
                                        </td>
                                        <td>
                                            <?php
                                            $createdAt = '—';
                                            if (!empty($userRow['created_at'])) {
                                                try {
                                                    $createdAt = (new DateTime($userRow['created_at']))->format('d M Y H:i');
                                                } catch (Throwable $ignored) {
                                                    $createdAt = (string) $userRow['created_at'];
                                                }
                                            }
                                            ?>
                                            <?= htmlspecialchars($createdAt, ENT_QUOTES) ?>
                                        </td>
                                        <td class="table-actions">
                                            <a class="btn btn-outline" href="<?= admin_url('users') ?>?edit=<?= (int) $userRow['id'] ?>">Edit</a>
                                            <?php if ($currentUser === null || $currentUser['id'] !== (int) $userRow['id']): ?>
                                                <form method="post" onsubmit="return confirm('Hapus user ini?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int) $userRow['id'] ?>">
                                                    <button type="submit" class="btn btn-outline btn-danger">Hapus</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        </main>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            const flashPayload = <?php
                echo json_encode([
                    'notices' => $notices,
                    'errors' => $errors,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ?>;

            const escapeHtml = (value) => String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            if (Array.isArray(flashPayload.notices)) {
                flashPayload.notices.forEach(function (notice) {
                    if (!notice || !notice.message) {
                        return;
                    }
                    const icon = notice.type === 'warning' ? 'warning' : 'success';
                    const title = icon === 'warning' ? 'Perhatian' : 'Berhasil';
                    Swal.fire({
                        icon,
                        title,
                        text: notice.message,
                        confirmButtonColor: '#2563eb',
                    });
                });
            }

            if (Array.isArray(flashPayload.errors) && flashPayload.errors.length) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    html: '<ul style="text-align:left;margin:0;padding-left:18px;">' + flashPayload.errors.map(function (err) {
                        return '<li>' + escapeHtml(err) + '</li>';
                    }).join('') + '</ul>',
                    confirmButtonColor: '#dc2626',
                });
            }
        </script>
    </body>
</html>

