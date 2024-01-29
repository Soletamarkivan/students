<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: ../login/admin/login.php');
    exit();
}

$usertype = $_SESSION['usertype'];

include '../php/conn.php';

function enrollStudents($course_id, $year_level, $student_ids, $class_name)
{
    global $conn;

    // Insert enrolled students into available classes
    $query = "INSERT INTO student_class (class_id, student_id, year_level_id, course_id)
              SELECT c.id, ?, ?, ?, ?
              FROM enrollment_details ed
              JOIN class c ON ed.course_id = c.course_id AND ed.year_level_id = c.yearlevelid
              WHERE ed.course_id = ? AND ed.year_level_id = ? AND c.classname = ?";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die('Error preparing statement: ' . $conn->error);
    }

    // Loop through selected student_ids
    foreach ($student_ids as $student_id) {
        // Adjusted the number of placeholders to match the SQL query
        $stmt->bind_param("ssss", $student_id, $year_level, $course_id, $class_name);
        $stmt->execute();

        if ($stmt->error) {
            die('Error executing statement: ' . $stmt->error);
        }
    }

    $stmt->close();

    // Update the class column in the enrollment_details table
    $update_query = "UPDATE enrollment_details SET class = ? WHERE student_id IN (?)";
    $update_stmt = $conn->prepare($update_query);

    if (!$update_stmt) {
        die('Error preparing update statement: ' . $conn->error);
    }

    // Convert student_ids array to a comma-separated string
    $student_ids_str = implode(",", $student_ids);

    $update_stmt->bind_param("ss", $class_name, $student_ids_str);
    $update_stmt->execute();

    if ($update_stmt->error) {
        die('Error executing update statement: ' . $update_stmt->error);
    }

    $update_stmt->close();
}



// Fetch course options from the database
$course_options = [];
$course_query = "SELECT course_id, course_name FROM course";
$course_stmt = $conn->prepare($course_query);

if (!$course_stmt) {
    die('Error preparing course statement: ' . $conn->error);
}

$course_stmt->execute();
$course_result = $course_stmt->get_result();

if (!$course_result) {
    die('Error fetching course options: ' . $course_stmt->error);
}

while ($row = $course_result->fetch_assoc()) {
    $course_options[$row['course_id']] = $row['course_name'];
}

$course_stmt->close();

// Fetch year level options from the database
$year_level_options = [];
$year_level_query = "SELECT year_level_id, year_level FROM year_level";
$year_level_result = $conn->query($year_level_query);

if (!$year_level_result) {
    die('Error fetching year level options: ' . $conn->error);
}

while ($row = $year_level_result->fetch_assoc()) {
    $year_level_options[$row['year_level_id']] = $row['year_level'];
}

// Fetch student options from the database
$student_options = [];
$student_query = "SELECT student_id, CONCAT(surname, ', ', first_name) AS full_name FROM students";
$student_result = $conn->query($student_query);

if (!$student_result) {
    die('Error fetching student options: ' . $conn->error);
}

while ($row = $student_result->fetch_assoc()) {
    $student_options[$row['student_id']] = $row['full_name'];
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['enroll'])) {
    // Get form data
    $course_id = isset($_POST['course_id']) ? $_POST['course_id'] : null;
    $year_level = $_POST['year_level'];
    $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];  // Assuming student_ids is an array from your form
    $class_name = $_POST['class_name'];

    // Call the enrollment function
    enrollStudents($course_id, $year_level, $student_ids, $class_name);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Assign Class</title>
</head>

<body class="font-[roboto-serif]">
    <div class="flex justify-start overflow-y-hidden">
        <div>
            <?php include './sidebar.php'; ?>
        </div>
        <div class="w-full py-4 px-4">
            <div>
                <?php include './topbar.php'; ?>
            </div>

            <form method="post" action="" class="mb-4">
                <section class="mt-8 font-sans">
                    <h2 class="text-2xl font-bold mb-3 uppercase">Class</h2>

                    <div class="bg-blue-300 px-6 py-4 rounded-lg shadow-md">
                        <div class="flex justify-between mb-3">
                            <h3 class="font-medium pt-1.5">Filter Table By:</h3>
                            <input type="submit" name="enroll" value="Enroll" class="inline-flex items-center gap-1.5 text-gray-900 bg-white hover:bg-gray-100 border border-gray-200 focus:ring-4 focus:outline-none focus:ring-gray-100 font-medium rounded-lg text-sm px-3 py-1.5 text-center inline-flex items-center">
                        </div>

                        <hr class="border-white mb-3">

                        <div class="inline-flex gap-5">
                            <div>
                                <label for="course_id" class="mb-2 text-base font-medium text-gray-900">Course:</label>
                                <select name="course_id" class="w-96 bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-0.5" required>
                                    <option>Course</option>
                                    <?php foreach ($course_options as $id => $name) : ?>
                                        <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="year_level" class="mb-2 text-base font-medium text-gray-900">Year Level:</label>
                                <select name="year_level" class="w-56 bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-0.5" required>
                                    <option>Year Level</option>
                                    <?php foreach ($year_level_options as $id => $level) : ?>
                                        <option value="<?php echo $id; ?>"><?php echo $level; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Add a multiple select for student_ids -->
                            <div>
                                <label for="student_ids" class="mb-2 text-base font-medium text-gray-900">Select Students:</label>
                                <select name="student_ids[]" multiple class="w-64 bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-0.5" required>
                                    <?php foreach ($student_options as $id => $name) : ?>
                                        <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
    <label for="class_name" class="mb-2 text-base font-medium text-gray-900">Class Name:</label>
    <select name="class_name" class="w-96 bg-gray-50 border border-gray-300 text-gray-900 text-base rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-0.5" required>
        <option value="" disabled selected>Select a Year Level and Course first</option>
    </select>
</div>
                        </div>
                    </div>
                </section>
            </form>

            <!-- ... remaining code ... -->
            <section class="border border-blue-300 font-sans relative overflow-x-auto shadow-md sm:rounded-lg">
                <div class="py-4 bg-blue-300">
                    <h2 class="px-6 text-lg font-semibold">Assigned Faculty</h2>
                </div>

                <table id="assignments_table" class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr class="border-b border-blue-300">
                            <th scope="col" class="px-6 py-3 border-r border-blue-300">
                                Course
                            </th>
                            <th scope="col" class="px-6 py-3 border-r border-blue-300">
                                Year Level
                            </th>
                            <th scope="col" class="px-6 py-3 border-r border-blue-300">
                                Class
                            </th>
                            <th scope="col" class="px-6 py-3 border-r border-blue-300">
                                Semester
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr class="bg-white hover:bg-gray-50">
                            <td class="px-6 py-3 border-b border-r border-blue-300">Sample</td>
                            <td class="px-6 py-3 border-b border-r border-blue-300">Sample</td>
                            <td class="px-6 py-3 border-b border-r border-blue-300">Sample</td>
                            <td class="px-6 py-3 border-b border-r border-blue-300">Sample</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
    <!-- ... remaining code ... -->

    <script>
        document.addEventListener('DOMContentLoaded', function () {
    const courseSelect = document.querySelector('[name="course_id"]');
    const yearLevelSelect = document.querySelector('[name="year_level"]');
    const classNameSelect = document.querySelector('[name="class_name"]');
    const fetchClassesUrl = 'fetch_studclass.php';

    // Function to fetch available classes based on course and year level
    function fetchAvailableClasses() {
        const selectedCourse = courseSelect.value;
        const selectedYearLevel = yearLevelSelect.value;

        // Make an asynchronous request to fetch available classes
        fetch(`${fetchClassesUrl}?course_id=${selectedCourse}&year_level=${selectedYearLevel}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Update the class_name select element with the fetched data
                classNameSelect.innerHTML = '';
                
                // Add a default option
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.disabled = true;
                defaultOption.selected = true;
                defaultOption.textContent = 'Select a class';
                classNameSelect.appendChild(defaultOption);

                for (const className of data) {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    classNameSelect.appendChild(option);
                }
            })
            .catch(error => console.error('Error fetching available classes:', error));
    }

    // Add an event listener to the course select element
    courseSelect.addEventListener('change', fetchAvailableClasses);

    // Add an event listener to the year level select element
    yearLevelSelect.addEventListener('change', fetchAvailableClasses);
});

    </script>

    <script src="../assets/js/adminSidebar.js" defer></script>
</body>

</html>
