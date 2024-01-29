<?php
session_start();

if (!isset($_SESSION['student_number'])) {
    header("Location: ../login/student/login.php");
    exit();
}

include '../php/conn.php';

date_default_timezone_set('Asia/Manila');

$studentNumber = isset($_SESSION['student_number']) ? $_SESSION['student_number'] : null;

$sql = "SELECT st.first_name, st.surname, st.middle_name, st.suffix, si.status, ci.city, ci.mobile_number, ci.email, si.profile_picture, ed.course_id, cr.course_name
        FROM student_number sn 
        JOIN school_account sa ON sn.student_number_id = sa.student_number_id
        JOIN student_information si ON sa.school_account_id = si.school_account_id
        JOIN students st ON si.student_id = st.student_id
        JOIN enrollment_details ed ON si.enrollment_details_id = ed.enrollment_details_id
        JOIN course cr ON ed.course_id = cr.course_id
        JOIN contact_information ci ON si.contact_information_id = ci.contact_information_id
        WHERE sn.student_number = ?";

$stmt = $conn->prepare($sql);

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
    $city = $row['city'];
    $mobile_number = $row['mobile_number'];
    $email = $row['email'];
    $suffix = $row['suffix'];

    $stmt->close();
} else {
    echo 'No or more than one row returned from the SQL query.';
    exit();
}

function getCurrentSchoolYearAndSemester() {
    $currentMonth = date('n');

    $startMonth = ($currentMonth >= 7) ? 7 : 1;

    $startYear = ($currentMonth >= 7) ? date('Y') : date('Y') - 1;
    $endYear = $startYear + 1;

    $semester = ($currentMonth >= 5 && $currentMonth <= 12 ? 'First Semester' : 'Second Semester');

    if(isset($_GET['semister'])) {
        switch($_GET['semister']) {
            case 'First semester':
                $semester = 'First semester';
                break;
            case 'Second semester':
                $semester = 'Second semester';
                break;
        }
    }

    $schoolYear = $startYear . '-' . $endYear;

    return array('schoolYear' => $schoolYear, 'semester' => $semester);
}

$schoolInfo = getCurrentSchoolYearAndSemester();
$schoolYear = $schoolInfo['schoolYear'];
$semester = $schoolInfo['semester'];

$selectedCourse = $row['course_id'];

$courses = [
    1 => "Bachelor of Science in Information Technology",
    2 => "Bachelor of Science in Hospitality Management",
    3 => "Bachelor of Science in Business Administration",
    4 => "Bachelor of Elementary Education",
    5 => "Bachelor of Secondary Education major in English",
    6 => "Bachelor of Secondary Education major in Mathematics",
    7 => "Bachelor of Science in Criminology"
];

$selectedYearLevel = isset($row['year_level_id']) ? $row['year_level_id'] : 0;

$yearLevels = [
    1 => "First Year",
    2 => "Second Year",
    3 => "Third Year",
    4 => "Fourth Year"
];

$semesters = [
    1 => "First Semester",
    2 => "Second Semester",
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['saveSubjects'])) {
    $selectedCourse = $_POST['course'];
    $selectedYearLevel = $_POST['yearLevel'];
    $admissionType = $_POST['admissionType'];
    // $currentMonth = date('n');
    $selectedSemester = $_POST['semester'];
    // $selectedSemester = ($currentMonth >= 5 && $currentMonth <= 12) ? 1 : 2;

    $uploadDirectory = 'uploads/';
    $updateSql = "";

    if(isset($_FILES['file'])) {
        $uploadedFile = $_FILES['file'];

        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            // die('File upload failed with error code ' . $uploadedFile['error']);
        }

        $destinationPath = $uploadDirectory . uniqid() . basename($uploadedFile['name']);
        move_uploaded_file($uploadedFile['tmp_name'], $destinationPath);
        $updateSql = "UPDATE enrollment_details AS ed
                    JOIN student_information AS si ON ed.enrollment_details_id = si.enrollment_details_id
                    JOIN students AS st ON si.student_id = st.student_id
                    JOIN student_number AS sn ON st.student_number_id = sn.student_number_id
                    SET ed.course_id = ?, ed.year_level_id = ?, ed.semester_tbl_id = ?, ed.admission_type = ?, ed.school_year = ?, ed.upload_file = '". $destinationPath ."', si.status = 'Enlisted'
                    WHERE sn.student_number = ?";

    } else {
        $updateSql = "UPDATE enrollment_details AS ed
                    JOIN student_information AS si ON ed.enrollment_details_id = si.enrollment_details_id
                    JOIN students AS st ON si.student_id = st.student_id
                    JOIN student_number AS sn ON st.student_number_id = sn.student_number_id
                    SET ed.course_id = ?, ed.year_level_id = ?, ed.semester_tbl_id = ?, ed.admission_type = ?, ed.school_year = ?, si.status = 'Enrolled'
                    WHERE sn.student_number = ?";
    }

    $updateStmt = $conn->prepare($updateSql);

    if (!$updateStmt) {
        die("Error in SQL query: " . $conn->error);
    }

    $updateStmt->bind_param("iiissi", $selectedCourse, $selectedYearLevel, $selectedSemester, $admissionType, $schoolYear, $studentNumber);
    $updateStmt->execute();

    if ($updateStmt->error) {
        die("Error updating enrollment details: " . $updateStmt->error);
    }

    $updateStmt->close();

    $enrolledSubjects = isset($_POST['enrolled_subjects']) ? $_POST['enrolled_subjects'] : [];

    $insertSubjectsSql = "INSERT INTO enrolled_subjects (student_id, year_level_id, semester_tbl_id, subject_id, school_year)
                          VALUES (?, ?, ?, ?, ?)";
    $insertSubjectsStmt = $conn->prepare($insertSubjectsSql);

    if (!$insertSubjectsStmt) {
        die("Error in SQL query: " . $conn->error);
    }

    $yearLevelId = $selectedYearLevel;
    $semesterId = $selectedSemester; 

    foreach ($enrolledSubjects as $subjectId) {
        $insertSubjectsStmt->bind_param("iiiss", $studentId, $yearLevelId, $semesterId, $subjectId, $schoolYear);
        $insertSubjectsStmt->execute();

        if ($insertSubjectsStmt->error) {
            die("Error inserting enrolled subject: " . $insertSubjectsStmt->error);
        }
    }

    $insertSubjectsStmt->close();

    $notificationMessage = "$firstName $surname completed the enrollment process.";
    $notificationDatetime = date('Y-m-d H:i:s');
    $sqlUpdateNotification = "UPDATE notifications SET message = ?, datetime = ? WHERE message LIKE ?";
    $updateNotificationStmt = $conn->prepare($sqlUpdateNotification);
    $updateNotificationMessage = "%$firstName $surname%";
    $updateNotificationStmt->bind_param("sss", $notificationMessage, $notificationDatetime, $updateNotificationMessage);
    $updateNotificationStmt->execute();

    if ($updateNotificationStmt->error) {
        die("Error updating notification: " . $updateNotificationStmt->error);
    }

    $updateNotificationStmt->close();

    header("Location: paymentReference.php");

    exit();
}

if ($studentNumber) {
    $sql = "SELECT s.student_id
            FROM students s
            JOIN student_number sn ON s.student_number_id = sn.student_number_id
            WHERE sn.student_number = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error in SQL query: " . $conn->error);
    }

    $stmt->bind_param("s", $studentNumber);
    $stmt->execute();

    $stmt->bind_result($studentId);

    if (!$stmt->fetch()) {
        die("Error fetching student_id: " . $stmt->error);
    }

    $stmt->close();

} else {
    echo "Student Number not available in session.";
}
?>


    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="../assets/css/custom.css">
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <title>Admission Page</title>
    </head>
    <body>
        <div style="background: radial-gradient(at center, rgba(118, 163, 224, 0.5  ), #FFFFFF);">
            <div>
                <?php include './topbar.php'; ?>
            </div>
            <div class="w-full"><img src="../assets/img/admission-banner.png" class="w-full" alt=""></div>
            <div>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="w-full flex justify-center items-center">
                        <div class="w-1/2 p-4 border border-blue-800 border-opacity-20 p-2 my-4 rounded-md drop-shadow-md bg-white">
                            <div class="font-bold text-2xl mb-4">
                                Enrollment
                            </div>
                            <div class="text-base">
                                <div class="italic mb-6">
                                    Welcome to Our Lady of Lourdes College enrollment process! We're thrilled to guide you through the steps of completing your enrollment form and confirming your subjects. Once you've completed this process, you'll be ready to proceed to payment and officially enroll with us.
                                    <br><br>
                                    To get started, please provide the required information, confirm your subjects, and follow the instructions provided. If you have any questions or need assistance, feel free to reach out to our enrollment support team. We're here to help you every step of the way.
                                    <br><br>
                                    Thank you for choosing Our Lady of Lourdes College. We look forward to welcoming you to our community!
                                </div>
                                <div class="grid gap-2">
                                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id, ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="text-md font-semibold">You're Applying: </div>
                                    <div class="flex  items-center gap-2">
                                        <span class="text-sm font-semibold">Course:</span>
                                        <span><?= $courses[$selectedCourse] ?></span>
                                    </div>
                                    <input type="hidden" id="courseDropdown" name="course" value="<?= $selectedCourse ?>"/>
                                    <!-- <div class="flex  items-center gap-2">
                                        <span class="text-sm font-semibold">Course:</span>
                                        <select id="courseDropdown" name="course" class="text-sm p-1 border border-blue-200 rounded-md">
                                            <?php foreach ($courses as $courseId => $courseName) : ?>
                                                <option value="<?= $courseId ?>" <?= ($selectedCourse == $courseId) ? 'selected' : '' ?>>
                                                    <?= $courseName ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div> -->
                                    <div class="flex  items-center gap-2">
                                        <span class="text-sm font-semibold">Year Level:</span>
                                        <select id="yearLevelDropdown" name="yearLevel" class="text-sm p-1 border border-blue-200 rounded-md">
                                            <?php foreach ($yearLevels as $yearLevelId => $yearLevelName) : ?>
                                                <option value="<?= $yearLevelId ?>" <?= ($selectedYearLevel == $yearLevelId) ? 'selected' : '' ?>>
                                                    <?= $yearLevelName ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="flex  items-center gap-2">
                                        <span class="text-sm font-semibold">School Year:</span>
                                        <span><?= $schoolYear ?></span>
                                    </div>
                                    <div class="flex  items-center gap-2">
                                        <span class="text-sm font-semibold">Semester:</span>
                                        <select id="semesterDropdown" name="semester" class="text-sm p-1 border border-blue-200 rounded-md">
                                            <?php foreach ($semesters as $semesterId => $semesterName) : ?>
                                                <option value="<?= $semesterId ?>">
                                                    <?= $semesterName ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="flex  items-center gap-2">
                                        <div class="text-sm font-semibold">Admission Type:</div>
                                        <select name="admissionType" id="admissionType" class="text-sm p-1 border border-blue-200 rounded-md">
                                            <option value="New Student">New Student</option>
                                            <option value="Transferee">Transferee</option>
                                            <option value="Old student">Old student</option>
                                            <option value="Irregular">Irregular</option>
                                        </select>
                                    </div>
                                    <div class="container flex gap-2" id="uploadFileCon">
                                        <div class="text-sm font-semibold">Upload file:</div>
                                        <input accept="image/*" type="file" class="form-control" name="file">
                                    </div>
                                    <div id="subjectTableContainer">
                                        <table class="table-auto w-full">
                                            <thead>
                                                <tr id="trhead" class="justify-between bg-blue-200">
                                                    <td style="width: 20%;" class="text-center">Code</td>
                                                    <td style="width: 50%;" class="text-center">Subject</td>
                                                    <td style="width: 30%;" class="text-center">Units</td>
                                                    <td style="width: 30%;" class="text-center delete_row">Action</td>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    include '../php/conn.php';

                                                    $yearLevelMapping = [
                                                        'First Year' => 1,
                                                        'Second Year' => 2,
                                                        'Third Year' => 3,
                                                        'Fourth Year' => 4,
                                                    ];

                                                    $semesterMapping = [
                                                        'First Semester' => 1,
                                                        'Second Semester' => 2,
                                                    ];

                                                    $selectedYearLevel = array_search($row['year_level'], $yearLevels);
                                                    $selectedSemester = $semesterMapping[$semester];

                                                    $sql = "SELECT os.open_subject_id, s.subject_id, s.code, s.name, s.unit
                                                            FROM open_subjects os
                                                            JOIN subjects s ON os.subject_id = s.subject_id
                                                            WHERE os.course_id = ? AND os.year_level_id = ? AND os.semester_tbl_id = ?";

                                                    $stmt = $conn->prepare($sql);

                                                    if (!$stmt) {
                                                        die("Error in SQL query: " . $conn->error);
                                                    }

                                                    $stmt->bind_param("iii", $selectedCourse, $selectedYearLevel, $selectedSemester);
                                                    $stmt->execute();

                                                    if ($stmt->error) {
                                                        die("Error in query execution: " . $stmt->error);
                                                    }

                                                    $result = $stmt->get_result();

                                                    $row_number = 0;  

                                                    if ($result->num_rows > 0) {
                                                        while ($row = $result->fetch_assoc()) {
                                                            $row_number++;
                                                            $row_class = ($row_number % 2 == 0) ? 'bg-white' : 'bg-blue-100';
                                                    
                                                            $openSubjectsId = isset($row['open_subject_id']) ? $row['open_subject_id'] : '';
                                                            echo "<tr class='$row_class' id='tr_$row_number'>
                                                                <td class='pl-16 hidden'><input type=\"text\" name=\"enrolled_subjects[]\" value=\"{$row['subject_id']}\"></td>
                                                                <td class='pl-16'>{$row['code']}</td>
                                                                <td class='text-center'>{$row['name']}</td>
                                                                <td class='text-center'>{$row['unit']}</td>
                                                                <td class='text-center delete_row'>
                                                                <button class='bg-red-200 px-2 py-1 rounded' onclick='deleteRow($row_number)'>Delete</button>
                                                                </td>
                                                            </tr>";
                                                           
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='4' class='text-center'>No subjects found</td></tr>";
                                                    }                                                    

                                                    $stmt->close();
                                                    mysqli_close($conn);
                                                    ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <script>
                                        function deleteRow(id) {
                                            if(confirm('Are you sure to remove subject?')) {
                                                document.querySelector('#tr_' + id).remove()
                                            } 
                                            return;
                                        }
                                    </script>

                                    

                                    <div class="flex items-center justify-center gap-4">
                                        <button class="p-2 bg-blue-400 rounded-md text-xs text-white hover:text-white hover:bg-blue-700 my-4" type="submit" name="saveSubjects">Proceed to Payment</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <script>
            $(document).ready(function () {
                const initialSetup = () => {
                    $(".delete_row").css("display", "none")
                    $("#uploadFileCon").css("display", "none")
                }
                initialSetup();

                // const delete_row = document.querySelectorAll('.delete_row');
                
                

                $('#admissionType').on('change', function() {
                    const type = $("#admissionType").val();
                    switch(type){
                        case 'Transferee': 
                            $('#subjectTableContainer').css("display", "none");
                            $("#uploadFileCon").css("display", "block")
                            $(".delete_row").css("display", "none")
                            break;
                        case 'Irregular': 
                            $('#subjectTableContainer').css("display", "block");
                            $("#uploadFileCon").css("display", "none")
                            $(".delete_row").css("display", "table-cell")
                            break;
                        default: 
                            $('#subjectTableContainer').css("display", "block");
                            $("#uploadFileCon").css("display", "none")
                            $(".delete_row").css("display", "none")
                            break;
                    }
                })                            

                // Event listener for course dropdown
                $('#courseDropdown').on('change', function () {
                    updateSubjectTable();
                });

                // Event listener for year level dropdown
                $('#yearLevelDropdown').on('change', function () {
                    updateSubjectTable();
                });

                // Event listener for semester dropdown
                $('#semesterDropdown').on('change', function () {
                    updateSubjectTable();
                });

                // Function to update the subject table
                function updateSubjectTable() {
                    // Fetch selected values
                    var selectedCourse = $('#courseDropdown').val();
                    var selectedYearLevel = $('#yearLevelDropdown').val();
                    var selectedSemester = $('#semesterDropdown').val();

                    // Make an AJAX request to update the subject table
                    console.log({ course: selectedCourse, yearLevel: selectedYearLevel, semester: selectedSemester })
                    $.ajax({
                        type: 'POST',
                        url: 'update_subjects.php',
                        data: { course: selectedCourse, yearLevel: selectedYearLevel, semester: selectedSemester },
                        success: function (data) {
                            // console.log('Received data:', data); // Log the entire HTML content
                            // Update the subject table container with the fetched data
                            $('#subjectTableContainer').html(data);
                            $('#admissionType').trigger("change")
                        },
                        error: function (error) {
                            console.log('Error updating subject table:', error);
                        }
                    });
                }
            });
        </script>

    </body>
    </html>
