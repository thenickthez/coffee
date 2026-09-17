<?php
require_once __DIR__ . '/../config/auth.php';
require_admin();

$pdo = db();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } elseif (password_verify($newPassword, $admin['password_hash'])) {
        $error = 'New password must be different from your current password.';
    } else {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
        $update->execute([$hash, $_SESSION['admin_id']]);
        $success = 'Password changed successfully.';
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Change Password | Coffee Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="admin-shell">
<header class="admin-header">
    <div class="container nav">
        <div class="logo">COFFEE<span>ADMIN</span></div>
        <nav class="admin-nav"><a href="index.php">Dashboard</a><a href="drinks.php">Drinks</a><a href="milk.php">Milk Options</a><a href="completed.php">Completed Orders</a><a href="../kitchen/">Kitchen</a></nav>
    </div>
</header>
<main class="container admin-main">
    <div class="password-page">
        <span class="eyebrow">ADMIN SECURITY</span>
        <h1 class="admin-page-title">Change Password</h1>
        <p class="password-intro">Update the password used to access the Coffee Admin and Kitchen.</p>

        <div class="panel password-panel">
            <?php if ($error): ?>
                <div class="notice notice-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="notice notice-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
                <div class="form-row">
                    <label for="current_password">Current Password</label>
                    <input id="current_password" type="password" name="current_password" autocomplete="current-password" required>
                </div>
                <div class="form-row">
                    <label for="new_password">New Password</label>
                    <input id="new_password" type="password" name="new_password" autocomplete="new-password" minlength="8" required>
                    <small class="field-help">Minimum 8 characters.</small>
                </div>
                <div class="form-row">
                    <label for="confirm_password">Confirm New Password</label>
                    <input id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" minlength="8" required>
                </div>
                <button class="btn btn-yellow" type="submit">CHANGE PASSWORD</button>
            </form>
        </div>
    </div>
</main>
<footer class="admin-footer"><div class="container admin-footer-inner"><div class="admin-footer-name">COFFEE <span>ADMIN</span></div><div class="admin-footer-links"><a href="password.php">Change Password</a><a href="logout.php">Log out</a></div></div></footer></body>
</html>
