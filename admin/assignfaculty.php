<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: ../login/admin/login.php');
    exit();
}

$usertype = $_SESSION['usertype'] ?? 'Admin';

include '../php/conn.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $subjectId = $_POST['subject'];
    $courseId = $_POST['course']; // Add this line to get the selected course ID
    $yearLevel = $_POST['year_level'];
    $class = $_POST['class'];
    $semester = $_POST['semester'];
    $facultyId = $_POST['faculty'];


// Assuming you have a specific classname
$classname = $_POST['class'];

// Fetch the corresponding id from the class table
$getClassIdQuery = "SELECT id FROM class WHERE classname = ?";
$stmtClassId = $conn->prepare($getClassIdQuery);
$stmtClassId->bind_param("s", $classname);
$stmtClassId->execute();
$stmtClassId->bind_result($classId);
$stmtClassId->fetch();
$stmtClassId->close();

// Now you can use $classId in your INSERT statement
$sql = "INSERT INTO faculty_subject_assignment (subject_id, year_level_id, class, semester, faculty_id)
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiss", $subject, $year_level, $classId, $semester, $faculty);
$stmt->execute();
$stmt->close();

 // Now you can use $courseId in your queries or insert statements

    // Fetch the corresponding id from the course table
    $getCourseIdQuery = "SELECT id FROM course WHERE course_id = ?";
    $stmtCourseId = $conn->prepare($getCourseIdQuery);
    $stmtCourseId->bind_param("i", $courseId);
    $stmtCourseId->execute();
    $stmtCourseId->bind_result($courseDbId);
    $stmtCourseId->fetch();
    $stmtCourseId->close();

    // Redirect to prevent form resubmission
    header("Location: assignfaculty.php");
    exit();
}

// Fetch assignments for display
$sqlFetchAssignments = "SELECT * FROM faculty_subject_assignment";
$resultAssignments = $conn->query($sqlFetchAssignments);
$assignments = [];

if (!$resultAssignments) {
    die("Query failed: " . $conn->error);
}

if ($resultAssignments->num_rows > 0) {
    while ($row = $resultAssignments->fetch_assoc()) {
        $assignments[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Assign Faculty</title>
    <script>
function updateSubjects() {
    var courseSelect = document.getElementById('course');
    var subjectSelect = document.getElementById('subject');

    // Get the selected course value
    var courseId = courseSelect.value;

    // Fetch subjects from the server based on the selected course
    var url = '../php/fetch_subjects.php?course_id=' + courseId;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            // Update the subject dropdown with the fetched subjects
            subjectSelect.innerHTML = ''; // Clear existing options
            if (data.length > 0) {
                data.forEach(subject => {
                    var option = document.createElement('option');
                    option.value = subject.subject_id;
                    option.textContent = subject.name;
                    subjectSelect.appendChild(option);
                });
            } else {
                var option = document.createElement('option');
                option.value = '';
                option.textContent = 'No subjects available';
                subjectSelect.appendChild(option);
            }
        })
        .catch(error => {
            console.error('Error fetching subjects:', error);
        });
}


        function updateClasses() {
            var yearLevelSelect = document.getElementById('year_level');
            var classSelect = document.getElementById('class');

            // Get the selected year level value
            var yearLevelValue = yearLevelSelect.value;

            // Fetch classes from the server based on the selected year level
            var url = 'fetch_classes.php?year_level=' + yearLevelValue;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // Update the class dropdown with the fetched classes
                    classSelect.innerHTML = ''; // Clear existing options
                    if (data.length > 0) {
                        data.forEach(className => {
                            var option = document.createElement('option');
                            option.value = className;
                            option.textContent = className;
                            classSelect.appendChild(option);
                        });
                    } else {
                        var option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'No classes available';
                        classSelect.appendChild(option);
                    }
                })
                .catch(error => {
                    console.error('Error fetching classes:', error);
                });
        }

        function updateTable() {
            // Fetch assignments from the server
            fetch('../php/process_assignment.php') // Corrected URL
                .then(response => response.json())
                .then(data => {
                    // Update the assignments table with the fetched data
                    var assignmentsTable = document.getElementById('assignments_table');
                    var tbody = assignmentsTable.querySelector('tbody');

                    // Clear existing rows
                    tbody.innerHTML = '';

                    // Add new rows based on the fetched data
                    data.forEach(assignment => {
                        var row = document.createElement('tr');

                        var columns = ['assignment_id', 'subject_id', 'year_level_id', 'class', 'semester', 'faculty_id'];

                        columns.forEach(column => {
                            var cell = document.createElement('td');
                            cell.textContent = assignment[column];
                            row.appendChild(cell);
                        });

                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error fetching assingment:', error);
                });
                
        }

        function submitForm(event) {
    event.preventDefault();

    // Form data
    var formData = new FormData(event.target);

    // Submit form data using fetch
    fetch('../php/process_assignment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        // Check the content type of the response
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text(); // If not JSON, return the response as text
        }
    })
    .then(data => {
        // Check for success or error message in the data
        console.log(data); // Log the response for debugging
        // Update the assignments table with the new data if needed
        updateTable();
    })
    .catch(error => {
        console.error('Error submitting form:', error);
    });
}


    </script>
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

            <form onsubmit="submitForm(event)" class="mb-4">
                <section class="mt-8 font-sans">
                    <h2 class="text-2xl font-bold mb-3 uppercase">Assign Faculty to Subject</h2>

                    <div class="bg-blue-300 px-6 py-4 rounded-lg shadow-md">
                        <div class="flex justify-between mb-3">
                            <h3 class="font-medium pt-1.5">Filter Table By:</h3>

                            <!-- Submit Button -->
                            <button type="submit" class="inline-flex items-center gap-1.5 text-gray-900 bg-white hover:bg-gray-100 border border-gray-200 focus:ring-4 focus:outline-none focus:ring-gray-100 font-medium rounded-lg text-sm px-3 py-1.5 text-center inline-flex items-center">
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                                        <path d="M5.25 6.375a4.125 4.125 0 1 1 8.25 0 4.125 4.125 0 0 1-8.25 0ZM2.25 19.125a7.125 7.125 0 0 1 14.25 0v.003l-.001.119a.75.75 0 0 1-.363.63 13.067 13.067 0 0 1-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 0 1-.364-.63l-.001-.122ZM18.75 7.5a.75.75 0 0 0-1.5 0v2.25H15a.75.75 0 0 0 0 1.5h2.25v2.25a.75.75 0 0 0 1.5 0v-2.25H21a.75.75 0 0 0 0-1.5h-2.25V7.5Z" />
                                    </svg>
                                </span>
                                Assign Faculty
                            </button>
                        </div>

                        <hr class="border-white mb-3">


                        <div class="inline-flex gap-5">
                            
                        <!-- Course Selection -->

<div>
    <label for="course" class="mb-2 text-base font-medium text-gray-900">Select Course:</label>
    <select name="course" id="course" class="w-60 bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-0.5" onchange="updateSubjects()" required>
        <option>Select Course</option>
        <?php
        // Fetch courses from the database
        $sqlCourses = "SELECT course_id, course_name FROM course";
        $resultCourses = $conn->query($sqlCourses);

        if ($resultCourses->num_rows > 0) {
            while ($row = $resultCourses->fetch_assoc()) {
                echo "<option value='" . $row['course_id'] . "'>" . $row['course_name'] . "</option>";
            }
        } else {
            echo "<option value=''>No courses available</option>";
        }

        $resultCourses->close();
        ?>
    </select>
</div>


                        <!-- Subject Selection -->
                        <div>
    <label for="subject" class="mb-2 text-base font-medium text-gray-900">Select Subject:</label>
    <select name="subject" id="subject" class="bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-0.5" required>
        <?php
        // Fetch subjects from the database based on the selected course
        $courseId = $_POST['course'] ?? ''; // Assuming 'course' is the name attribute of the course dropdown

        if (!empty($courseId)) {
            $sqlSubjects = "SELECT subject_id, name FROM subjects WHERE course_id = ?";
            $stmtSubjects = $conn->prepare($sqlSubjects);
            $stmtSubjects->bind_param("i", $courseId);
            $stmtSubjects->execute();
            $resultSubjects = $stmtSubjects->get_result();

            if ($resultSubjects->num_rows > 0) {
                while ($row = $resultSubjects->fetch_assoc()) {
                    $selected = ($row['subject_id'] == $_POST['subject']) ? 'selected' : '';
                    echo "<option value='" . $row['subject_id'] . "' $selected>" . $row['name'] . "</option>";
                }
            } else {
                echo "<option value=''>No subjects available for this course</option>";
            }

            $stmtSubjects->close();
        } else {
            echo "<option value=''>Select a course first</option>";
        }
        ?>
    </select>
</div>

                            <!-- Year Level Selection -->
                            <div>
                                <label for="year_level" class="mb-2 text-base font-medium text-gray-900">Select Year Level:</label>
                              
                                <select name="year_level" id="year_level" class="w-40 bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-0.5" onchange="updateClasses()" required>
                                <option>Select Year Level</option>
                                   <?php
                                    // Fetch year levels from the database
                                    $sqlYearLevels = "SELECT year_level_id, year_level FROM year_level";
                                    $resultYearLevels = $conn->query($sqlYearLevels);

                                    if ($resultYearLevels->num_rows > 0) {
                                        while ($row = $resultYearLevels->fetch_assoc()) {
                                            echo "<option value='" . $row['year_level_id'] . "'>" . $row['year_level'] . "</option>";
                                        }
                                    } else {
                                        echo "<option value=''>No year levels available</option>";
                                    }

                                    $resultYearLevels->close();
                                    ?>
                                </select>
                            </div>

                            <!-- Class Selection -->
                            <div>
                                <label for="class" class="mb-2 text-base font-medium text-gray-900">Select Class:</label>
                                <select name="class" id="class" class="w-35 bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-0.5" required>
                                    <option value="">Select a Year Level first</option>
                                </select>
                            </div>

                            <!-- Semester Selection -->
                            <div>
                                <label for="semester" class="mb-2 text-base font-medium text-gray-900">Select Semester:</label>
                                
                                <select name="semester" class="w-35 bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-0.5" required>
                                <option>Select Semester</option>
                                <option value="1">First Semester</option>
                                    <option value="2">Second Semester</option>
                                    <!-- Add more options as needed -->
                                </select>
                            </div>

                            <!-- Faculty Member Selection -->
                            <div>
                                <label for="faculty" class="mb-2 text-base font-medium text-gray-900">Select Faculty Member:</label>
                                <select name="faculty" class="w-52 bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-0.5" required>
                                <option>Faculty</option>
                                    <?php
                                    // Fetch faculty members from the database
                                    $sqlFaculty = "SELECT professor_details_id, surname, first_name, middle_name FROM professor_details";
                                    $resultFaculty = $conn->query($sqlFaculty);

                                    if ($resultFaculty->num_rows > 0) {
                                        while ($row = $resultFaculty->fetch_assoc()) {
                                            echo "<option value='" . $row['professor_details_id'] . "'>" . $row['surname'] . "</option>";
                                        }
                                    } else {
                                        echo "<option value=''>No faculty members available</option>";
                                    }

                                    $resultFaculty->close();
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </section>
            </form>

            <section class="border border-blue-300 font-sans relative overflow-x-auto shadow-md sm:rounded-lg">
                <div class="py-4 bg-blue-300">
                    <h2 class="px-6 text-lg font-semibold">Assigned Faculty</h2>
                </div>

                <table id="assignments_table" class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr class="border-b border-blue-300">
                            <th scope="col" class="px-6 py-3 border-r border-blue-300">
                                Subject Name
                            </th>
                            <th scope="col" class="px-6 py-3 border-r border-blue-300">
                                Year Level
                            </th>
                            <th scope="col" class="px-6 py-3 border-r border-blue-300">
                                Class Name
                            </th>
                            <th scope="col" class="px-6 py-3 border-r border-blue-300">
                                Semester
                            </th>
                            <th scope="col" class="px-6 py-3 border-r border-blue-300">
                                Professor
                            </th>

                        </tr>
                    </thead>

                    <tbody>
    <?php foreach ($assignments as $assignment) : ?>
        <tr class="bg-white hover:bg-gray-50">
            <td class="px-6 py-3 border-b border-r border-blue-300">
                <!-- Fetch subject name from the database using subject_id -->
                <?php
                $subjectId = $assignment['subject_id'];
                $sqlSubjectName = "SELECT name FROM subjects WHERE subject_id = ?";
                $stmtSubjectName = $conn->prepare($sqlSubjectName);
                $stmtSubjectName->bind_param("s", $subjectId);
                $stmtSubjectName->execute();
                $stmtSubjectName->bind_result($subjectName);
                $stmtSubjectName->fetch();
                $stmtSubjectName->close();

                echo $subjectName;
                ?>
            </td>
            <td class="px-6 py-3 border-b border-r border-blue-300"><?= $assignment['year_level_id'] ?></td>
            <td class="px-6 py-3 border-b border-r border-blue-300"><?= $assignment['class'] ?></td>
            <td class="px-6 py-3 border-b border-r border-blue-300"><?= $assignment['semester'] ?></td>
            <td class="px-6 py-3 border-b border-r border-blue-300">
                <!-- Fetch faculty details from the database using faculty_id -->
                <?php
                $facultyId = $assignment['faculty_id'];
                $sqlFacultyDetails = "SELECT surname, first_name, middle_name FROM professor_details WHERE professor_details_id = ?";
                $stmtFacultyDetails = $conn->prepare($sqlFacultyDetails);
                $stmtFacultyDetails->bind_param("s", $facultyId);
                $stmtFacultyDetails->execute();
                $stmtFacultyDetails->bind_result($surname, $firstName, $middleName);
                $stmtFacultyDetails->fetch();
                $stmtFacultyDetails->close();

                echo "$surname, $firstName $middleName";
                ?>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
                </table>
            </section>
        </div>
    </div>

</body>

<script src="../assets/js/adminSidebar.js" defer></script>
</html>