<?php
// Assuming you have a database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "student_portal";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    $response = [
        'success' => false,
        'message' => 'Connection failed: ' . $conn->connect_error
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get data from the form
$subject = $_POST['subject'];
$year_level = $_POST['year_level'];
$class_name = $_POST['class']; // Assuming you receive the class name

// Fetch the corresponding class id from the class table
$getClassIdQuery = "SELECT id FROM class WHERE classname = ?";
$stmtClassId = $conn->prepare($getClassIdQuery);
$stmtClassId->bind_param("s", $class_name);
$stmtClassId->execute();
$stmtClassId->bind_result($class_id);
$stmtClassId->fetch();
$stmtClassId->close();

$semester = $_POST['semester'];
$faculty = $_POST['faculty'];

// Insert data into faculty_subject_assignment table
$sql = "INSERT INTO faculty_subject_assignment (subject_id, year_level_id, class, semester, faculty_id)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    $response = [
        'success' => false,
        'message' => 'Error in SQL query: ' . $conn->error
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$stmt->bind_param("isssi", $subject, $year_level, $class_id, $semester, $faculty);

$response = [];

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = "Assignment successful!";
} else {
    $response['success'] = false;
    $response['message'] = "Error: " . $stmt->error;
}

$stmt->close();

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>
