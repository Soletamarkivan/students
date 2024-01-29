<?php
include '../php/conn.php';

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['course_id']) && isset($_GET['year_level'])) {
    $course_id = $_GET['course_id'];
    $year_level = $_GET['year_level'];

    $query = "SELECT DISTINCT c.classname
              FROM enrollment_details ed
              JOIN class c ON ed.course_id = c.course_id AND ed.year_level_id = c.yearlevelid
              WHERE ed.course_id = ? AND ed.year_level_id = ?";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die('Error preparing statement: ' . $conn->error);
    }

    $stmt->bind_param("ss", $course_id, $year_level);
    $stmt->execute();

    if ($stmt->error) {
        die('Error executing statement: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $class_names = [];

    while ($row = $result->fetch_assoc()) {
        $class_names[] = $row['classname'];
    }

    echo json_encode($class_names);

    $stmt->close();
} else {
    // Handle invalid requests or other cases as needed
    echo json_encode(['error' => 'Invalid request']);
}

mysqli_close($conn);
?>
