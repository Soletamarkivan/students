<?php
include '../php/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get the selected course ID from the query parameters
    $courseId = $_GET['course_id'];

    // Fetch subjects based on the selected course
    $sqlSubjects = "SELECT subject_id, name FROM subjects WHERE course_id = ?";
    $stmtSubjects = $conn->prepare($sqlSubjects);
    $stmtSubjects->bind_param("i", $courseId);
    $stmtSubjects->execute();
    $resultSubjects = $stmtSubjects->get_result();
    $subjects = [];

    while ($row = $resultSubjects->fetch_assoc()) {
        $subjects[] = $row;
    }

    $stmtSubjects->close();

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($subjects);
} else {
    header('HTTP/1.1 400 Bad Request');
    echo 'Bad Request';
}
?>
