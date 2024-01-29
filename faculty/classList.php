<?php
// Include your database connection file here
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: ../login/faculty/login.php');
    exit();
}

$usertype = $_SESSION['usertype'] ?? 'Faculty';
include '../php/conn.php';

// Fetch data from the database, joining with the students table
$sql = "SELECT sc.classname, sc.yearlevelid, s.surname, s.first_name, s.middle_name
        FROM studentclass sc
        JOIN students s ON sc.student_id = s.student_id";
$result = $conn->query($sql);

$classListTable = "<table class='min-w-full bg-white border border-gray-300'>";
$classListTable .= "<thead><tr class='bg-gray-200 text-gray-700'><th class='py-2 px-4 border-b'>Class Name</th><th class='py-2 px-4 border-b'>Year Level ID</th><th class='py-2 px-4 border-b'>Surname</th><th class='py-2 px-4 border-b'>First Name</th><th class='py-2 px-4 border-b'>Middle Name</th></tr></thead><tbody>";

// Output data of each row
while ($row = $result->fetch_assoc()) {
    $classListTable .= "<tr><td class='py-2 px-4 border-b'>" . $row["classname"] . "</td><td class='py-2 px-4 border-b'>" . $row["yearlevelid"] . "</td><td class='py-2 px-4 border-b'>" . $row["surname"] . "</td><td class='py-2 px-4 border-b'>" . $row["first_name"] . "</td><td class='py-2 px-4 border-b'>" . $row["middle_name"] . "</td></tr>";
}

$classListTable .= "</tbody></table>";

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Class List</title>
</head>

<body class="font-[roboto-serif]">
    <form action="../login/faculty/logout.php">
        <div class="flex justify-start overflow-y-hidden">
            <div>
                <?php include './sidebar.php'; ?>
            </div>
            <div class="w-full py-4 px-4">
                <div>
                    <?php include './topbar.php'; ?>
                </div>
                <h2 class="text-2xl font-bold mb-3 uppercase">Students Class</h2>
                <!-- Display the class list table -->
                <div class="mt-4 overflow-hidden bg-white p-8 rounded-lg shadow-md">
                    <?php echo $classListTable; ?>
                </div>
            </div>
        </div>
    </form>

    <script src="../assets/js/studentSidebar.js"></script>
</body>

</html>
