<?php
session_start();
include __DIR__ . '/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);

$sql = "
    SELECT message, created_at
    FROM notifications
    WHERE user_id = '$user_id'
    ORDER BY created_at DESC
    LIMIT 5
";

$result = mysqli_query($conn, $sql);
$data = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}

echo json_encode($data);