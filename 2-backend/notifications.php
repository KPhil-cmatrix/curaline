<?php

function createNotification($conn, $user_id, $message) {
    $user_id = mysqli_real_escape_string($conn, $user_id);
    $message = mysqli_real_escape_string($conn, $message);

    $sql = "
        INSERT INTO notifications (user_id, message, created_at, is_read)
        VALUES ('$user_id', '$message', NOW(), 0)
    ";

    return mysqli_query($conn, $sql);
}

function send_email_notification($to, $subject, $message) {
    // Email notifications disabled for final deployment.
    // In-app notifications are used instead :)
    return false;
}