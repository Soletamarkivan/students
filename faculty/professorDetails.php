<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: ../login/faculty/login.php');
    exit();
}

$usertype = $_SESSION['usertype'] ?? 'Faculty';

// Include your database connection code
include '../php/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the form is submitted
    $lastName = $_POST['last_name'];
    $firstName = $_POST['first_name'];
    $middleName = $_POST['middle_name'];

    // Get the user_id of the logged-in faculty
    $userId = $_SESSION['user_id'];

    // Check if the record already exists
    $sqlCheck = "SELECT * FROM professor_details WHERE user_id = ?";
    $stmtCheck = $conn->prepare($sqlCheck);

    if (!$stmtCheck) {
        die("Error in SQL query: " . $conn->error);
    }

    $stmtCheck->bind_param("i", $userId);
    $stmtCheck->execute();
    $stmtCheck->store_result();
    
    if ($stmtCheck->num_rows > 0) {
        // Update the existing record
        $sqlUpdate = "UPDATE professor_details SET surname = ?, first_name = ?, middle_name = ? WHERE user_id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);

        if (!$stmtUpdate) {
            die("Error in SQL query: " . $conn->error);
        }

        $stmtUpdate->bind_param("sssi", $lastName, $firstName, $middleName, $userId);
        $stmtUpdate->execute();

        $stmtUpdate->close();
    } else {
        // Insert a new record
        $sqlInsert = "INSERT INTO professor_details (user_id, surname, first_name, middle_name) VALUES (?, ?, ?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);

        if (!$stmtInsert) {
            die("Error in SQL query: " . $conn->error);
        }

        $stmtInsert->bind_param("isss", $userId, $lastName, $firstName, $middleName);
        $stmtInsert->execute();

        $stmtInsert->close();
    }

    $stmtCheck->close();
}

// Retrieve the current professor details for the logged-in faculty
$sqlSelect = "SELECT surname, first_name, middle_name FROM professor_details WHERE user_id = ?";
$stmtSelect = $conn->prepare($sqlSelect);

if (!$stmtSelect) {
    die("Error in SQL query: " . $conn->error);
}

$stmtSelect->bind_param("i", $userId);
$stmtSelect->execute();
$stmtSelect->bind_result($surname, $firstName, $middleName);
$stmtSelect->fetch();
$stmtSelect->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Professor Details Form</title>
    <!-- Your head content here -->
</head>

<body class="font-[roboto-serif]">
    <div class="flex justify-start">
        <div>
            <?php include './sidebar.php'; ?>
        </div>
        <div class="w-full py-4 px-4">
            <div>
                <?php include './topbar.php'; ?>
            </div>
            <div>
                <h2 class="text-2xl font-bold mb-3 uppercase">Update Your Name</h2>
                <div class="bg-white p-8 rounded-lg shadow-md mx-4 w-6/12">
                    <section>
                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            // Display the updated professor details
                            echo '<p class="text-green-600 mb-4">Successfully updated professor details.</p>';
                        }
                        ?>

                        <form method="post" action="">
                            <div class="mb-4">
                                <label for="last_name" class="block text-sm font-medium text-gray-600">Last Name:</label>
                                <input type="text" name="last_name" class="mt-1 p-2 w-full border border-gray-300 rounded-md" value="<?php echo $surname; ?>" required>
                            </div>

                            <div class="mb-4">
                                <label for="first_name" class="block text-sm font-medium text-gray-600">First Name:</label>
                                <input type="text" name="first_name" class="mt-1 p-2 w-full border border-gray-300 rounded-md" value="<?php echo $firstName; ?>" required>
                            </div>

                            <div class="mb-6">
                                <label for="middle_name" class="block text-sm font-medium text-gray-600">Middle Name:</label>
                                <input type="text" name="middle_name" class="mt-1 p-2 w-full border border-gray-300 rounded-md" value="<?php echo $middleName; ?>" required>
                            </div>

                            <div>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring focus:border-blue-300">Submit</button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
            <script src="../assets/js/adminSidebar.js" defer></script>
        </div>
    </div>
</body>
</html>
