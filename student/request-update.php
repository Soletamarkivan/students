<?php

if (!isset($_SESSION['student_number'])) {
    header("Location: ../login/student/login.php");
    exit();
}

include '../php/conn.php';

date_default_timezone_set('Asia/Manila');

$studentNumber = $_SESSION['student_number'];

$successMessage = "";

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["requestSubmit"])) {
    $message = trim($_POST["message"]);
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    $insertQuery = "INSERT INTO request_messages (student_number, message, request_datetime) VALUES (?, ?, NOW())";
    $insertStmt = $conn->prepare($insertQuery);

    if (!$insertStmt) {
        die("Error preparing statement: " . $conn->error);
    }

    if (!$insertStmt->bind_param("is", $studentNumber, $message)) {
        die("Error binding parameters: " . $insertStmt->error);
    }

    if (!$insertStmt->execute()) {
        $successMessage = "Error saving request message: " . $insertStmt->error;
    } else {
        $successMessage = "Request message saved successfully.";
    }

    $sqlError = $insertStmt->error;
    if (!empty($sqlError)) {
        die("SQL Error after execution: " . $sqlError);
    }

    $insertStmt->close();
}

// TOR Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["requestSubmitTor"])) {
    $message = trim($_POST["message"]);
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    // Assuming you have a separate table called 'request_letters'
    $insertQuery = "INSERT INTO request_tor (student_number, document_type, message, request_datetime, release_date) VALUES (?, 'Transcript of Record', ?, NOW(), CURDATE() + INTERVAL 7 DAY)";
    $insertStmt = $conn->prepare($insertQuery);

    if (!$insertStmt) {
        die("Error preparing statement: " . $conn->error);
    }

    if (!$insertStmt->bind_param("is", $studentNumber, $message)) {
        die("Error binding parameters: " . $insertStmt->error);
    }

    if (!$insertStmt->execute()) {
        $successMessage = "Error saving request message: " . $insertStmt->error;
    } else {
        // Retrieve the release date from the database
        $releaseDateQuery = "SELECT release_date FROM request_tor WHERE student_number = ? ORDER BY request_datetime DESC LIMIT 1";
        $releaseDateStmt = $conn->prepare($releaseDateQuery);
        $releaseDateStmt->bind_param("i", $studentNumber);
        $releaseDateStmt->execute();
        $releaseDateResult = $releaseDateStmt->get_result();

        if ($row = $releaseDateResult->fetch_assoc()) {
            $releaseDate = $row['release_date'];
            // Add the logic to show the release date modal here
            echo "<script>openReleaseDateModal('$releaseDate');</script>";
        }

        $successMessage = "Request message saved successfully.";
    }

    $sqlError = $insertStmt->error;
    if (!empty($sqlError)) {
        die("SQL Error after execution: " . $sqlError);
    }

    $insertStmt->close();
    $releaseDateStmt->close();
}



// Good Moral Request --- edit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["requestSubmitGoodmoral"])) {
    $message = trim($_POST["message"]);
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    $insertQuery = "INSERT INTO request_goodmoral (student_number, message, request_datetime) VALUES (?, ?, NOW())";
    $insertStmt = $conn->prepare($insertQuery);

    if (!$insertStmt) {
        die("Error preparing statement: " . $conn->error);
    }

    if (!$insertStmt->bind_param("is", $studentNumber, $message)) {
        die("Error binding parameters: " . $insertStmt->error);
    }

    if (!$insertStmt->execute()) {
        $successMessage = "Error saving request message: " . $insertStmt->error;
    } else {
        $successMessage = "Request message saved successfully.";
    }

    $sqlError = $insertStmt->error;
    if (!empty($sqlError)) {
        die("SQL Error after execution: " . $sqlError);
    }

    $insertStmt->close();
}

// Honorable Dismissal Request --- edit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["requestSubmitHonorable"])) {
    $message = trim($_POST["message"]);
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    $insertQuery = "INSERT INTO request_honorable (student_number, message, request_datetime) VALUES (?, ?, NOW())";
    $insertStmt = $conn->prepare($insertQuery);

    if (!$insertStmt) {
        die("Error preparing statement: " . $conn->error);
    }

    if (!$insertStmt->bind_param("is", $studentNumber, $message)) {
        die("Error binding parameters: " . $insertStmt->error);
    }

    if (!$insertStmt->execute()) {
        $successMessage = "Error saving request message: " . $insertStmt->error;
    } else {
        $successMessage = "Request message saved successfully.";
    }

    $sqlError = $insertStmt->error;
    if (!empty($sqlError)) {
        die("SQL Error after execution: " . $sqlError);
    }

    $insertStmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

</head>

<body>
    <div class="w-full flex justify-center mt-2">
        <button type="button" onclick="openUpdateModal()" class="rounded-sm py-2 px-4 font-medium border border-blue-500 hover:bg-blue-400 hover:text-white">Update Details</button>
    </div>

    <div id="updateModal" class="hidden fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full h-full bg-black bg-opacity-50 items-center justify-center">
        <div class="relative p-4 w-auto top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 max-w-2xl max-h-full">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700 p-4">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="flex items-center justify-between border-b rounded-t dark:border-gray-600">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Request for updating information
                        </h3>
                    </div>
                    <div>
                        <i>
                            <p class="text-sm leading-relaxed text-gray-500 dark:text-gray-400 cursor-pointer">Note: Some of your information cannot be changed, and some can be considered with a valid reason. Details that can be changed include email, contact number, address, height, weight, parent's details aside from name, and emergency contact details.</p>
                        </i>
                    </div>
                    <div class="mt-2">
                        Tell us what you want to change.
                    </div>
                    <div>
                        <textarea id="message" name="message" rows="4" class="w-full p-2 border border-blue-300 rounded-md focus:outline-none resize-none"></textarea>
                    </div>
                    <div class="flex items-center justify-center p-4 gap-4">
                        <button type="submit" name="requestSubmit" class="bg-blue-500 text-white px-4 py-2 rounded-md">Submit</button>
                        <button type="button" onclick="closeUpdateModal()" class="bg-gray-500 text-white px-4 py-2 rounded-md">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="border-t-2 border-b-2 border-blue-300 mt-4 px-2 py-3">
        <h3 class="text-lg font-semibold mb-2">Request Documents</h3>

        <div class="flex justify-between px-5 mb-3">
            <p class="pt-1.5">Transcript of Records</p>
            <button type="button" onclick="openTorModal()" class="rounded-sm py-1 px-2.5 border border-blue-500 hover:bg-blue-400 hover:text-white">Request</button>

            <!-- Modal of TOR -->
            <div id="TorModal" class="hidden fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full h-full bg-black bg-opacity-50 items-center justify-center">
                <div class="relative p-4 w-auto top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 max-w-2xl max-h-full">
                    <div class="relative bg-white rounded-lg shadow dark:bg-gray-700 p-4">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="flex items-center justify-between border-b rounded-t dark:border-gray-600 mb-3">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    Request of Document (Transcript of Record)
                                </h3>
                            </div>
                            <span class="font-semibold">Important Notice: <p>Please take a screenshot of this prompt (message). You will need to show it to the registrar (window 1) as part of the verification process.</p> </span><br>
                            <p class="text-sm leading-relaxed text-gray-500">Student Name: <span class="font-semibold"><?= $surname ?>, <?= $firstName ?> <?= $middleName ?>.<?= $suffix ?></span></p>
                            <p class="text-sm leading-relaxed text-gray-500">Student Number: <span class="font-semibold"><?= $studentNumber ?></span> </p>
                            <p class="text-sm leading-relaxed text-gray-500">Request Date:
                                <span class="font-semibold"><?php
                                                            // Get the current date
                                                            $currentDate = date("Y-m-d");

                                                            // Format the current date as "Month Day, Year" (e.g., January 11, 2023)
                                                            $formattedDate = date('F j, Y', strtotime($currentDate));

                                                            // Display the result
                                                            echo $formattedDate;
                                                            ?>

                                </span>
                            </p>
                            <p class="mb-4">Release Date: <span class="font-semibold">
                                    <?php
                                    // Get the current date
                                    $currentDate = date('Y-m-d');

                                    // Initialize a counter for skipped days
                                    $skippedDays = 0;

                                    // Loop until we find a date 7 days ahead (skipping Sundays)
                                    while ($skippedDays < 7) {
                                        // Increment the current date by one day
                                        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));

                                        // Check if the current day is not Sunday
                                        if (date('N', strtotime($currentDate)) != 7) {
                                            $skippedDays++;
                                        }
                                    }

                                    // Format the result as "Month Day, Year" (e.g., January 11, 2023)
                                    $formattedDate = date('F j, Y', strtotime($currentDate));

                                    // Display the result
                                    echo $formattedDate;
                                    ?>

                                </span></p>

                            <div class="mt-2">
                                Tell us the purpose of your request:
                            </div>
                            <div>
                                <textarea id="message" name="message" rows="4" class="w-full p-2 border border-blue-300 rounded-md focus:outline-none resize-none"></textarea>
                            </div>
                            <div class="flex items-center justify-center p-4 gap-4">
                                <button type="submit" name="requestSubmitTor" class="bg-blue-500 text-white px-4 py-2 rounded-md">Submit</button>
                                <button type="button" onclick="closeTorModal()" class="bg-gray-500 text-white px-4 py-2 rounded-md">Close</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-between px-5 mb-3">
            <p class="pt-1.5">Good Moral Certificate</p>
            <button type="button" onclick="openGoodmoralModal()" class="rounded-sm py-1 px-2.5 border border-blue-500 hover:bg-blue-400 hover:text-white">Request</button>

            <!-- Modal of Good Moral Certificate -->
            <div id="GoodmoralModal" class="hidden fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full h-full bg-black bg-opacity-50 items-center justify-center">
                <div class="relative p-4 w-auto top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 max-w-2xl max-h-full">
                    <div class="relative bg-white rounded-lg shadow dark:bg-gray-700 p-4">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="flex items-center justify-between border-b rounded-t dark:border-gray-600 mb-3">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    Request of Document (Good Moral)
                                </h3>
                            </div>

                            <span class="font-semibold">Important Notice: <p>Please take a screenshot of this prompt (message). You will need to show it to the registrar (window 1) as part of the verification process.</p> </span><br>
                            <p class="text-sm leading-relaxed text-gray-500">Student Name: <span class="font-semibold"><?= $surname ?>, <?= $firstName ?> <?= $middleName ?>.<?= $suffix ?></span></p>
                            <p class="text-sm leading-relaxed text-gray-500">Student Number: <span class="font-semibold"><?= $studentNumber ?></span> </p>
                            <p class="text-sm leading-relaxed text-gray-500">Request Date:
                                <span class="font-semibold"><?php
                                                            // Get the current date
                                                            $currentDate = date("Y-m-d");

                                                            // Format the current date as "Month Day, Year" (e.g., January 11, 2023)
                                                            $formattedDate = date('F j, Y', strtotime($currentDate));

                                                            // Display the result
                                                            echo $formattedDate;
                                                            ?>

                                </span>
                            </p>
                            <p class="mb-4">Release Date: <span class="font-semibold">
                                    <?php
                                    // Get the current date
                                    $currentDate = date('Y-m-d');

                                    // Initialize a counter for skipped days
                                    $skippedDays = 0;

                                    // Loop until we find a date 7 days ahead (skipping Sundays)
                                    while ($skippedDays < 7) {
                                        // Increment the current date by one day
                                        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));

                                        // Check if the current day is not Sunday
                                        if (date('N', strtotime($currentDate)) != 7) {
                                            $skippedDays++;
                                        }
                                    }

                                    // Format the result as "Month Day, Year" (e.g., January 11, 2023)
                                    $formattedDate = date('F j, Y', strtotime($currentDate));

                                    // Display the result
                                    echo $formattedDate;
                                    ?>

                                </span></p>


                            <div class="mt-2">
                                Tell us the purpose of your request:
                            </div>
                            <div>
                                <textarea id="message" name="message" rows="4" class="w-full p-2 border border-blue-300 rounded-md focus:outline-none resize-none"></textarea>
                            </div>
                            <div class="flex items-center justify-center p-4 gap-4">
                                <button type="submit" name="requestSubmitGoodmoral" class="bg-blue-500 text-white px-4 py-2 rounded-md">Submit</button>
                                <button type="button" onclick="closeGoodmoralModal()" class="bg-gray-500 text-white px-4 py-2 rounded-md">Close</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        

        <div class="flex justify-between px-5 mb-3">
            <p class="pt-1.5">Certificate of Honorable Dismissal</p>
            <button type="button" onclick="openHonorableModal()" class=" rounded-sm py-1 px-2.5 border border-blue-500 hover:bg-blue-400 hover:text-white">Request</button>

            <!-- Modal of Honorable Dismissal Certificate -->
            <div id="HonorableModal" class="hidden fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full h-full bg-black bg-opacity-50 items-center justify-center">
                <div class="relative p-4 w-auto top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 max-w-2xl max-h-full">
                    <div class="relative bg-white rounded-lg shadow dark:bg-gray-700 p-4">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="flex items-center justify-between border-b rounded-t dark:border-gray-600 mb-3">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    Request of Document (Honorable Dismissal)
                                </h3>
                            </div>

                            <span class="font-semibold">Important Notice: <p>Please take a screenshot of this prompt (message). You will need to show it to the registrar (window 1) as part of the verification process.</p> </span><br>
                            <p class="text-sm leading-relaxed text-gray-500">Student Name: <span class="font-semibold"><?= $surname ?>, <?= $firstName ?> <?= $middleName ?>.<?= $suffix ?></span></p>
                            <p class="text-sm leading-relaxed text-gray-500">Student Number: <span class="font-semibold"><?= $studentNumber ?></span> </p>
                            <p class="text-sm leading-relaxed text-gray-500">Request Date:
                                <span class="font-semibold"><?php
                                                            // Get the current date
                                                            $currentDate = date("Y-m-d");

                                                            // Format the current date as "Month Day, Year" (e.g., January 11, 2023)
                                                            $formattedDate = date('F j, Y', strtotime($currentDate));

                                                            // Display the result
                                                            echo $formattedDate;
                                                            ?>

                                </span>
                            </p>
                            <p class="mb-4">Release Date: <span class="font-semibold">
                                    <?php
                                    // Get the current date
                                    $currentDate = date('Y-m-d');

                                    // Initialize a counter for skipped days
                                    $skippedDays = 0;

                                    // Loop until we find a date 7 days ahead (skipping Sundays)
                                    while ($skippedDays < 7) {
                                        // Increment the current date by one day
                                        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));

                                        // Check if the current day is not Sunday
                                        if (date('N', strtotime($currentDate)) != 7) {
                                            $skippedDays++;
                                        }
                                    }

                                    // Format the result as "Month Day, Year" (e.g., January 11, 2023)
                                    $formattedDate = date('F j, Y', strtotime($currentDate));

                                    // Display the result
                                    echo $formattedDate;
                                    ?>

                                </span></p>

                            <div class="mt-2">
                                Tell us the purpose of your request:
                            </div>

                            <div>
                                <textarea id="message" name="message" rows="4" class="w-full p-2 border border-blue-300 rounded-md focus:outline-none resize-none"></textarea>
                            </div>
                            <div class="flex items-center justify-center p-4 gap-4">
                                <button type="submit" name="requestSubmitHonorable" class="bg-blue-500 text-white px-4 py-2 rounded-md">Submit</button>
                                <button type="button" onclick="closeHonorableModal()" class="bg-gray-500 text-white px-4 py-2 rounded-md">Close</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


</body>

</html>