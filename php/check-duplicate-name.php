<?php
include 'conn.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $surname = $_POST["surname"];
    $firstname = $_POST["firstname"];

    $checkDuplicateQuery = "SELECT * FROM students WHERE surname = '$surname' AND first_name = '$firstname'";
    $duplicateResult = $conn->query($checkDuplicateQuery);

    if ($duplicateResult->num_rows > 0) {
        echo json_encode(["status" => "success", "exists" => true]);
    } else {
        echo json_encode(["status" => "success", "exists" => false]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
}
?>
