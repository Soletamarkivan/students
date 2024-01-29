<?php
include '../php/conn.php';

date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $requestId = mysqli_real_escape_string($conn, $_POST['id']);

    $message = 'Your personal details are updated.';

    $datetime = date('Y-m-d H:i:s');

    $deleteQuery = "DELETE FROM request_messages WHERE id = '$requestId'";
    $result = mysqli_query($conn, $deleteQuery);

    if ($result) {
        $insertQuery = "INSERT INTO student_notifications (message, datetime) VALUES ('$message', '$datetime')";
        $insertResult = mysqli_query($conn, $insertQuery);

        if ($insertResult) {
            echo 'Request deleted successfully, and notification added.';
        } else {
            echo 'Error adding notification: ' . mysqli_error($conn);
        }
    } else {
        echo 'Error deleting request: ' . mysqli_error($conn);
    }

    mysqli_close($conn);
} else {
    echo 'Invalid request';
}
?>
