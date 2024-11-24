<?php
// admin/delete_page.php

session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$pageId = $_GET['id'] ?? null;
if ($pageId) {
    $stmt = $pdo->prepare("DELETE FROM pages WHERE id = :id");
    $stmt->execute(['id' => $pageId]);
}

header('Location: manage_pages.php');
exit;
?>
