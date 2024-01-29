<?php
session_start();

if (!isset($_SESSION['student_number'])) {
    header("Location: ../login/student/login.php");
    exit();
}

include '../php/conn.php';

date_default_timezone_set('Asia/Manila');

$studentNumber = $_SESSION['student_number'];

$sqlStudentInfo = "SELECT st.first_name, st.surname, st.middle_name, st.suffix, si.status, si.profile_picture, ed.course_id, cr.course_name, yl.year_level, smt.semester,ed.class
        FROM student_number sn 
        JOIN school_account sa ON sn.student_number_id = sa.student_number_id
        JOIN student_information si ON sa.school_account_id = si.school_account_id
        JOIN enrollment_details ed ON si.enrollment_details_id = ed.enrollment_details_id
        JOIN course cr ON ed.course_id = cr.course_id
        JOIN year_level yl ON ed.year_level_id = yl.year_level_id
        JOIN semester_tbl smt ON ed.semester_tbl_id = smt.semester_tbl_id
        JOIN students st ON si.student_id = st.student_id
        WHERE sn.student_number = ?";

$stmtStudentInfo = $conn->prepare($sqlStudentInfo);

if (!$stmtStudentInfo) {
    die("Error in SQL query: " . $conn->error);
}

$stmtStudentInfo->bind_param("s", $studentNumber);
$stmtStudentInfo->execute();
$resultStudentInfo = $stmtStudentInfo->get_result();

if ($resultStudentInfo === false) {
    die("Error in query execution: " . $stmtStudentInfo->error);
}

if ($resultStudentInfo->num_rows == 1) {
    $row = $resultStudentInfo->fetch_assoc();

    $firstName = $row['first_name'];
    $surname = $row['surname'];
    $middleName = $row['middle_name'] ?? '';
    $status = $row['status'];
    $course = $row['course_name'];
    $suffix = $row['suffix'];
    $yearLevel = $row['year_level'];
    $semester_name = $row['semester'];
    $status= $row['status'];
    $class= $row['class'] ?? 'No section assigned.';
    $profilePicturePath = $row['profile_picture'];

    $stmtStudentInfo->close();
} else {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_subjects'])) {
    $enrolledSubjects = $_POST['enroll_subjects']; 

    echo "Enrolled Subjects Array: ";
    var_dump($enrolledSubjects);

    $sqlDeleteEnrolledSubjects = "DELETE FROM enrolled_subjects WHERE student_id = ?";
    $stmtDeleteEnrolledSubjects = $conn->prepare($sqlDeleteEnrolledSubjects);

    if (!$stmtDeleteEnrolledSubjects) {
        die("Error in SQL query: " . $conn->error);
    }

    $stmtDeleteEnrolledSubjects->bind_param("s", $studentNumber);
    $stmtDeleteEnrolledSubjects->execute();

    $stmtDeleteEnrolledSubjects->close();

    $sqlInsertEnrolledSubjects = "INSERT INTO enrolled_subjects (student_id, subject_id) VALUES (?, ?)";
    $stmtInsertEnrolledSubjects = $conn->prepare($sqlInsertEnrolledSubjects);

    if (!$stmtInsertEnrolledSubjects) {
        die("Error in SQL query: " . $conn->error);
    }

    $stmtInsertEnrolledSubjects->bind_param("ss", $studentNumber, $subjectId);

    foreach ($enrolledSubjects as $subjectId) {
        $stmtInsertEnrolledSubjects->execute();
    }

    $stmtInsertEnrolledSubjects->close();

    header("Location: dashboard.php");
    exit();
}

$sqlEnrolledSubjects = "SELECT es.subject_id, s.code, s.name, s.unit
                       FROM enrolled_subjects es
                       JOIN subjects s ON es.subject_id = s.subject_id
                       WHERE es.student_id = ?";

$stmtEnrolledSubjects = $conn->prepare($sqlEnrolledSubjects);

if (!$stmtEnrolledSubjects) {
    die("Error in SQL query: " . $conn->error);
}

$stmtEnrolledSubjects->bind_param("s", $studentNumber);
$stmtEnrolledSubjects->execute();
$resultEnrolledSubjects = $stmtEnrolledSubjects->get_result();

if ($resultEnrolledSubjects === false) {
    die("Error in query execution: " . $stmtEnrolledSubjects->error);
}

$enrolledSubjects = $resultEnrolledSubjects->fetch_all(MYSQLI_ASSOC);

$stmtEnrolledSubjects->close();
$enrolled_subjects_query = "SELECT 
                                yl.year_level, 
                                stbl.semester, 
                                sbj.code, 
                                sbj.name, 
                                sbj.unit
                            FROM 
                                students
                                JOIN enrolled_subjects ON students.student_id = enrolled_subjects.student_id
                                JOIN subjects sbj ON enrolled_subjects.subject_id = sbj.subject_id
                                JOIN student_number ON students.student_number_id = student_number.student_number_id
                                JOIN year_level yl ON enrolled_subjects.year_level_id = yl.year_level_id
                                JOIN semester_tbl stbl ON enrolled_subjects.semester_tbl_id = stbl.semester_tbl_id
                            WHERE student_number.student_number = ?";

$enrolled_stmt = $conn->prepare($enrolled_subjects_query);
$enrolled_stmt->bind_param("s", $studentNumber);
$enrolled_stmt->execute();
$enrolled_result = $enrolled_stmt->get_result();

if ($enrolled_result === false) {
    die("Error in query execution: " . $enrolled_stmt->error);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Student Dashboard</title>
    <style>
        section {
            margin: 20px;
        }

        h2 {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #648EC7;
            color: white;
        }
    </style>
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
                <div class="overflow-y-auto" style="height: 800px;">
                    <section class="inline-flex gap-8">
                        <div class="ml-4">
                            <?php if (empty($profilePicturePath)) : ?>
                                <img src="../assets/svg/profile.svg" class="w-48 h-48 mx-auto" alt="Default Profile Picture">
                            <?php else : ?>
                                <img src="<?= htmlspecialchars($profilePicturePath) ?>" class="w-48 h-48 mx-auto rounded-full" alt="Profile Picture">
                            <?php endif; ?>
                        </div>
                        <div class="pt-6">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars(str_replace(['.', ','], '', ucwords($firstName . ' ' . $middleName . ' ' . $surname)), ENT_QUOTES, 'UTF-8'); ?></p>


                            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($studentNumber, ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Program:</strong> <?php echo htmlspecialchars($course, ENT_QUOTES, 'UTF-8'); ?></p>
                       
                            <p><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($status, ENT_QUOTES, 'UTF-8')); ?></p>
                            <p><strong>Section:</strong> <?php echo ucfirst(htmlspecialchars($class, ENT_QUOTES, 'UTF-8')); ?></p>
                        </div>
                    </section>
                    <section>
                    <div>
                        <?php if ($enrolled_result->num_rows > 0) : ?>
                            <div class="w-full mt-8">
                                <h2 class="text-2xl font-bold mb-4">Enrolled Subjects</h2>
                                <?php
                                $currentYear = null;
                                $currentSemester = null;
                                ?>
                                <?php while ($enrolled_row = $enrolled_result->fetch_assoc()) : ?>
                                    <?php
                                    $yearLevel = $enrolled_row['year_level'];
                                    $semester = $enrolled_row['semester'];
                                    ?>
                                    <?php if ($yearLevel !== $currentYear || $semester !== $currentSemester) : ?>
                                        <?php
                                        if ($currentYear !== null && $currentSemester !== null) {
                                            echo '</tbody></table>'; 
                                        }
                                        ?>
                                        <h3 class="text-xl font-bold mb-2"><?= $yearLevel ?> - <?= $semester ?></h3>
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-blue-200">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Subject Code</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Subject Name</th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Unit</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                            <?php
                                            $currentYear = $yearLevel;
                                            $currentSemester = $semester;
                                            ?>
                                    <?php endif; ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= $enrolled_row['code'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= $enrolled_row['name'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?= $enrolled_row['unit'] ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody></table>
                            </div>
                        <?php else : ?>
                            <p>No enrolled subjects found.</p>
                        <?php endif; ?>
                    </div>
                    </section>
                    <script src="../assets/js/studentSidebar.js"></script>
                    <!-- Your existing JavaScript and other scripts... -->
                </div>
            </div>
        </div>
    </form>
</body>

</html>