<?php
session_start();

if (!isset($_SESSION['student_number'])) {
    header('Location: ../login/student/login.php');
    exit();
}

include '../php/conn.php';

$studentNumber = $_SESSION['student_number'];

// Fetch student information
$sqlStudentInfo = "SELECT st.first_name, st.surname, st.middle_name, st.suffix, si.status, si.profile_picture, ed.course_id, cr.course_name, yl.year_level, smt.semester
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
    $status = $row['status'];

    $stmtStudentInfo->close();
} else {
    header("Location: ../../login.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = mysqli_real_escape_string($conn, $_POST['new_password']);
    $student_number_id = $_SESSION['student_number'];

    if (!empty($newPassword)) {
        $updateQuery = "UPDATE school_account SET password = ? WHERE student_number_id = (SELECT student_number_id FROM student_number WHERE student_number = ?)";
        $updateStmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($updateStmt, "ss", $newPassword, $student_number_id);

        if (mysqli_stmt_execute($updateStmt)) {
            $message = "Password changed successfully!";
        } else {
            $message = "Error changing password: " . mysqli_stmt_error($updateStmt);
        }

        mysqli_stmt_close($updateStmt);
    } else {
        $message = "New password cannot be empty.";
    }
}

mysqli_close($conn);
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Change Password</title>
</head>

<body class="font-[roboto-serif]" style="background: rgba(118, 163, 224, 0.1);">
    <div class="w-full flex justify-start">
        <div>
            <?php include './sidebar.php'; ?>
        </div>
        <div class="w-full py-4 px-4">
            <div class="mb-4">
                <?php include './topbarStudent.php'; ?>
            </div>
            <div class="w-full bg-white p-4 border border-blue-100 gap-2">
                <div>
                    Change Password
                </div>
                <hr class="w-full h-px my-2 border-0 dark:bg-gray-700" style="background-color: #8EAFDC;">
                <div class="w-3/4 grid pr-2">
                    <div>
                        <form method="post" action="" class="max-w-md mx-auto bg-white p-6 rounded-md shadow-md">
                            <label for="new_password" class="block text-sm font-medium text-gray-600 mb-2">New Password:</label>
                            <div class="relative">
                                <input type="password" name="new_password" id="new_password" required class="w-full border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500">
                                <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center focus:outline-none">
                                    Show
                                </button>
                            </div>
                            <div class="mt-2">
                                <input type="submit" value="Change Password" class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 cursor-pointer">
                            </div>
                        </form>

                        <script>
                            document.getElementById('togglePassword').addEventListener('click', togglePassword);

                            function togglePassword() {
                                var passwordInput = document.getElementById('new_password');
                                var toggleButton = document.getElementById('togglePassword');

                                if (passwordInput.type === 'password') {
                                    passwordInput.type = 'text';
                                    toggleButton.textContent = 'Hide';
                                } else {
                                    passwordInput.type = 'password';
                                    toggleButton.textContent = 'Show';
                                }
                            }
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/adminSidebar.js"></script>
</body>

</html>