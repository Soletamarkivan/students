<?php
session_start();

if (!isset($_SESSION['student_number'])) {
    header("Location: ../login/student/login.php");
    exit();
}

include '../php/conn.php';

date_default_timezone_set('Asia/Manila');

$studentNumber = $_SESSION['student_number'];

// Fetch student information
$studentInfoSql = "SELECT st.first_name, st.surname, st.middle_name, st.suffix, si.status, ed.course_id, si.profile_picture
                   FROM student_number sn 
                   JOIN school_account sa ON sn.student_number_id = sa.student_number_id
                   JOIN student_information si ON sa.school_account_id = si.school_account_id
                   JOIN students st ON si.student_id = st.student_id
                   JOIN enrollment_details ed ON si.enrollment_details_id = ed.enrollment_details_id
                   WHERE sn.student_number = ?";

$stmt = $conn->prepare($studentInfoSql);

if (!$stmt) {
    die("Error in SQL query: " . $conn->error);
}

$stmt->bind_param("s", $studentNumber);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    die("Error in query execution: " . $stmt->error);
}

if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();

    $firstName = $row['first_name'];
    $surname = $row['surname'];
    $middleName = $row['middle_name'] ?? '';
    $status = $row['status'];
    $course = $row['course_id'];
    $suffix = $row['suffix'];
    $profilePicturePath = $row['profile_picture'];

    $stmt->close();
} else {
    header("Location: ../../login.php");
    exit();
}

// Fetch student grades with subject names
$gradesSql = "SELECT s.name AS subject_name, es.prelim, es.midterm, es.finals, es.total
              FROM enrolled_subjects es
              JOIN subjects s ON es.subject_id = s.subject_id
              WHERE es.student_id = (SELECT student_id FROM student_number WHERE student_number = ?)";

$gradesStmt = $conn->prepare($gradesSql);

if (!$gradesStmt) {
    die("Error in SQL query: " . $conn->error);
}

$gradesStmt->bind_param("s", $studentNumber);
$gradesStmt->execute();
$gradesResult = $gradesStmt->get_result();

if ($gradesResult === false) {
    die("Error in query execution: " . $gradesStmt->error);
}

$grades = [];

while ($gradeRow = $gradesResult->fetch_assoc()) {
    $grades[] = $gradeRow;
}

$gradesStmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Student Grades</title>
</head>

<body class="font-serif"> 
    <form action="../login/student/logout.php">
        <div class="flex">
            <div>
                <?php include './sidebar.php'; ?>
            </div>
            <div class="w-full py-4 px-4">
                <div>
                    <?php include './topbarStudent.php'; ?>
                </div>
                <div class="py-4 px-6">
                    <h2 class="text-2xl font-semibold mb-4">Student Grades</h2>
                    <?php if (empty($grades)) : ?>
                        <p>No grades available for this student.</p>
                    <?php else : ?>
                        <table class="table-auto">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2">Subject Name</th>
                                    <th class="px-4 py-2">Prelim Grade</th>
                                    <th class="px-4 py-2">Midterm Grade</th>
                                    <th class="px-4 py-2">Finals Grade</th>
                                    <th class="px-4 py-2">Average</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $grade) : ?>
                                    <tr>
                                        <td class="border px-4 py-2"><?php echo htmlspecialchars($grade['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="border px-4 py-2"><?php echo number_format($grade['prelim'], 2); ?></td>
                                        <td class="border px-4 py-2"><?php echo number_format($grade['midterm'], 2); ?></td>
                                        <td class="border px-4 py-2"><?php echo number_format($grade['finals'], 2); ?></td>
                                        <td class="border px-4 py-2"><?php echo number_format($grade['total'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
    <script src="../assets/js/studentSidebar.js"></script>
</body>

</html>
