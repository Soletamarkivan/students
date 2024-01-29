<?php
require '../../vendor/autoload.php';
include '../../php/conn.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];
    $token = $_POST["token"];

    $password = mysqli_real_escape_string($conn, $password);
    $confirmPassword = mysqli_real_escape_string($conn, $confirmPassword);
    $token = mysqli_real_escape_string($conn, $token);

    if ($password !== $confirmPassword) {
        echo "Passwords do not match.";
        exit;
    }

    $validateTokenQuery = "SELECT * FROM password_reset WHERE token = '$token'";
    $resultToken = $conn->query($validateTokenQuery);

    if ($resultToken === false) {
        echo "Error validating token: " . $conn->error;
        exit;
    }

    if ($resultToken->num_rows > 0) {
        $rowToken = $resultToken->fetch_assoc();
        $userId = $rowToken['user_id'];

        $updatePasswordQuery = "UPDATE school_account SET password = '$password' WHERE student_number_id = '$userId'";

        if ($conn->query($updatePasswordQuery) === false) {
            echo "Error updating password: " . $conn->error;
            exit;
        }

        $deleteTokenQuery = "DELETE FROM password_reset WHERE token = '$token'";
        if ($conn->query($deleteTokenQuery) === false) {
            echo "Error deleting token: " . $conn->error;
            exit;
        }

        header("Location: login.php?message=" . urlencode("Password reset successful.\nYou can now login with your new password."));

        exit;
    } else {
        echo "Invalid or expired token.";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body>
    <div class="w-screen h-screen inline flex ">
        <div class="justify-center items-center inline-flex w-full bg-[url('../../assets/img/school1.png')] bg-no-repeat bg-cover bg-center">
            <div class="backdrop-blur-sm absolute inset-0 w-full h-full"></div>
            <div class="p-14 bg-white rounded-2xl drop-shadow-xl border border-blue-800 border-opacity-60">
                <div id="messageContainer" class="text-sm text-red-500 mb-2"></div>
                <div class="justify-center items-center inline-flex gap-1">
                    <div><img src="../../assets/svg/ollcLogoNoName.svg" class="w-[56px]" alt="OLLC Logo"></div>
                    <div class="text-2xl font-medium">INFORMATION SYSTEM</div>
                </div>
                <div class="my-4">
                    <form action="" method="post">
                        <div class='text-xl text-center font-medium'>Reset your Password</div>
                        <div class="my-2">
                            <label for="password">New Password:</label>
                            <div class="relative">
                                <input class='w-full text-sm p-1 border border-blue-200 rounded-md' type="password" id="password" name="password" required>
                                <span toggle="#password" class="password-toggle absolute right-0 top-1/2 transform -translate-y-1/2 cursor-pointer">
                                    <svg class="h-6 w-6" fill="none" stroke="gray" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2 12s3-6 10-6 10 6 10 6"></path>
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div class="my-2">
                            <label for="confirm_password">Confirm Password:</label>
                            <div class="relative">
                                <input class='w-full text-sm p-1 border border-blue-200 rounded-md' type="password" id="confirm_password" name="confirm_password" required>
                                <span toggle="#confirm_password" class="password-toggle absolute right-0 top-1/2 transform -translate-y-1/2 cursor-pointer">
                                    <svg class="h-6 w-6" fill="none" stroke="gray" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2 12s3-6 10-6 10 6 10 6"></path>
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <input type="hidden" name="token" value="<?php echo $_GET['token']; ?>">
                        <div>
                            <button class="bg-blue-400 mt-2 py-2 px-8 shadow justify-center items-center inline-flex gap-2 text-white rounded-full hover:bg-blue-600 hover:font-semibold" type="submit" name="submit">Reset Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(".password-toggle").click(function() {
            var input = $($(this).attr("toggle"));
            if (input.attr("type") == "password") {
                input.attr("type", "text");
            } else {
                input.attr("type", "password");
            }
        });
    </script>
    <script>
        function getParameterByName(name, url) {
            if (!url) url = window.location.href;
            name = name.replace(/[\[\]]/g, "\\$&");
            var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
                results = regex.exec(url);
            if (!results) return null;
            if (!results[2]) return '';
            return decodeURIComponent(results[2].replace(/\+/g, " "));
        }

        function displayMessage() {
            var message = getParameterByName('message');
            if (message) {
                $('#messageContainer').html('<p>' + message + '</p>');
            }
        }

        $(document).ready(function () {
            displayMessage();
        });
    </script>
</body>
</html>
