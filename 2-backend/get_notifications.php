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
    SELECT notification_id, message, created_at
    FROM notifications
    WHERE user_id = '$user_id'
      AND is_read = 0
    ORDER BY created_at DESC
    LIMIT 1
";

$result = mysqli_query($conn, $sql);
$data = [];

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $data[] = $row;

    $notification_id = (int)$row['notification_id'];

    mysqli_query($conn, "
        UPDATE notifications
        SET is_read = 1
        WHERE notification_id = $notification_id
          AND user_id = '$user_id'
    ");
}

echo json_encode($data);