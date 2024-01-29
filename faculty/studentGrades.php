<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: ../login/faculty/login.php');
    exit();
}

$usertype = $_SESSION['usertype'] ?? 'Faculty';

// Include your database connection code
include '../php/conn.php';

// Fetch subjects handled by the logged-in faculty
$faculty_id = $_SESSION['username'];

$faculty_subjects_query = "SELECT fsa.subject_id, s.name
                            FROM faculty_subject_assignment fsa
                            JOIN subjects s ON fsa.subject_id = s.subject_id
                            WHERE fsa.faculty_id = ?";
$faculty_subjects_stmt = $conn->prepare($faculty_subjects_query);
$faculty_subjects_stmt->bind_param("i", $faculty_id);
$faculty_subjects_stmt->execute();
$faculty_subjects_result = $faculty_subjects_stmt->get_result();

$faculty_subjects = [];
while ($row = $faculty_subjects_result->fetch_assoc()) {
    $faculty_subjects[$row['subject_id']] = $row['name'];
}
$faculty_subjects_stmt->close();

// Handle grade updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Assuming your form has input fields like prelim, midterm, finals, student_id, subject_id

    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $prelim = $_POST['prelim'];
    $midterm = $_POST['midterm'];
    $finals = $_POST['finals'];

    // Validate and update grades in the database
    $update_grades_query = "UPDATE enrolled_subjects
                            SET prelim = ?, midterm = ?, finals = ?
                            WHERE student_id = ? AND subject_id = ?";
    $update_grades_stmt = $conn->prepare($update_grades_query);
    $update_grades_stmt->bind_param("dddsi", $prelim, $midterm, $finals, $student_id, $subject_id);
    $update_grades_stmt->execute();
    $update_grades_stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Faculty Grades</title>
</head>

<body class="font-serif">

    <div class="flex">
        <div>
            <?php include './sidebar.php'; ?>
        </div>
        <div class="w-full py-4 px-4">
            <div>
                <?php include './topbar.php'; ?>
            </div>
            <h1 class="text-2xl font-bold mb-4">Faculty Grades</h1>

            <?php foreach ($faculty_subjects as $subject_id => $subject_name) : ?>
                <div class="mb-4">
                    <h2 class="text-xl font-semibold mb-2"><?= $subject_name ?></h2>

                    <?php
                    // Fetch enrolled students for the current subject
                    $enrolled_students_query = "SELECT es.student_id, s.surname, s.first_name, s.middle_name, es.prelim, es.midterm, es.finals
                                                FROM enrolled_subjects es
                                                JOIN students s ON es.student_id = s.student_id
                                                WHERE es.subject_id = ?";
                    $enrolled_students_stmt = $conn->prepare($enrolled_students_query);
                    $enrolled_students_stmt->bind_param("i", $subject_id);
                    $enrolled_students_stmt->execute();
                    $enrolled_students_result = $enrolled_students_stmt->get_result();

                    if ($enrolled_students_result->num_rows > 0) :
                    ?>

                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-300">
                                <thead>
                                    <tr>
                                        <th class="py-2 px-4 border-b">Student ID</th>
                                        <th class="py-2 px-4 border-b">Surname</th>
                                        <th class="py-2 px-4 border-b">First Name</th>
                                        <th class="py-2 px-4 border-b">Middle Name</th>
                                        <th class="py-2 px-4 border-b">Prelim</th>
                                        <th class="py-2 px-4 border-b">Midterm</th>
                                        <th class="py-2 px-4 border-b">Finals</th>
                                        <th class="py-2 px-4 border-b">Average</th>
                                        <th class="py-2 px-4 border-b">Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php while ($student = $enrolled_students_result->fetch_assoc()) : ?>
                                        <tr class="hover:bg-gray-100">
                                            <td class="py-2 px-4 border-b"><?= $student['student_id'] ?></td>
                                            <td class="py-2 px-4 border-b"><?= $student['surname'] ?></td>
                                            <td class="py-2 px-4 border-b"><?= $student['first_name'] ?></td>
                                            <td class="py-2 px-4 border-b"><?= $student['middle_name'] ?></td>
                                            <td class="py-2 px-4 border-b"><?= $student['prelim'] ?></td>
                                            <td class="py-2 px-4 border-b"><?= $student['midterm'] ?></td>
                                            <td class="py-2 px-4 border-b"><?= $student['finals'] ?></td>
                                            <td class="py-2 px-4 border-b"><?= number_format(($student['prelim'] + $student['midterm'] + $student['finals']) / 3, 2, '.', '') ?></td>
                                            <td class="py-2 px-4 border-b">
                                                <form method="POST">
                                                    <input type="hidden" name="student_id" value="<?= $student['student_id'] ?>">
                                                    <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                                                    <input type="text" name="prelim" value="<?= $student['prelim'] ?>" class="w-12 py-1 px-2 border border-gray-300">
                                                    <input type="text" name="midterm" value="<?= $student['midterm'] ?>" class="w-12 py-1 px-2 border border-gray-300">
                                                    <input type="text" name="finals" value="<?= $student['finals'] ?>" class="w-12 py-1 px-2 border border-gray-300">
                                                    <button type="submit" class="bg-blue-500 text-white py-1 px-2 rounded hover:bg-blue-600">Update</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>

                                </tbody>
                            </table>
                        </div>

                    <?php else : ?>
                        <p class="text-gray-500">No enrolled students for this subject.</p>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>
    </div>

</body>

<script src="../assets/js/adminSidebar.js" defer></script>
</html>

<?php
// Close the database connection
$conn->close();
?>