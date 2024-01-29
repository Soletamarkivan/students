<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: ../login/admin/login.php');
    exit();
}

$usertype = $_SESSION['usertype'];

include '../php/conn.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = mysqli_real_escape_string($conn, $_POST['new_password']);
    $username = $_SESSION['username'];

    if (!empty($newPassword)) {
        $updateQuery = "UPDATE usertbl SET password = ? WHERE username = ?";
        $updateStmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($updateStmt, "ss", $newPassword, $username);

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
                <?php include './topbar.php'; ?>
            </div>
            <div class="w-full bg-white p-4 border border-blue-100 gap-2">
                <div>
                    Change Password
                </div>
                <hr class="w-full h-px my-2 border-0 dark:bg-gray-700" style="background-color: #8EAFDC;">
                <div class="w-3/4 grid pr-2">
                    <div>
                        <form action="" method="post">
                            <div class="flex justify-start items-center gap-2 text-base">
                                <div>
                                    New Password:
                                </div>
                                <div class="text-sm p-1 border border-blue-200 rounded-md w-64 text-xs">
                                    <input type="password" name="new_password" placeholder="Enter a new password" class="px-1 w-full">
                                </div>
                                <div>
                                    <button class="p-2 bg-blue-500 rounded-md text-xs text-white hover:bg-blue-700">Change Password</button>
                                </div>
                            </div>
                            <div class="mt-2 text-sm text-red-500">
                                <?php echo $message; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/adminSidebar.js"></script>
</body>
</html>