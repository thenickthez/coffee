<?php
session_start();
require_once __DIR__ . '/database.php';

function require_admin(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login.php');
        exit;
    }
}
