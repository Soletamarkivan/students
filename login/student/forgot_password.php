<?php
require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include '../../php/conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $student_number_or_email = $_POST["student_number_or_email"];

    if (filter_var($student_number_or_email, FILTER_VALIDATE_EMAIL)) {
        $column = "email";
    } else {
        $column = "student_number";
    }

    $check_user_sql = "SELECT students.*, contact_information.email
                       FROM students
                       JOIN student_number ON students.student_number_id = student_number.student_number_id
                       JOIN student_information ON students.student_id = student_information.student_id
                       JOIN contact_information ON student_information.contact_information_id = contact_information.contact_information_id
                       WHERE $column = '$student_number_or_email'";

    $result = $conn->query($check_user_sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['student_id'];

        $token = bin2hex(random_bytes(32));

        $insert_token_sql = "INSERT INTO password_reset (user_id, token, timestamp) VALUES ($user_id, '$token', NOW())";
        $insert_result = $conn->query($insert_token_sql);

        if ($insert_result) {
            $reset_link = "http://localhost/student-portal/login/student/reset_password.php?token=$token";
            $mail = new PHPMailer(true); 

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->Port = 587;
                $mail->SMTPAuth = true;
                $mail->Username = 'ourladyoflourdescollege7@gmail.com';
                $mail->Password = 'lbue japy zvvp eyct';

                $mail->setFrom('ourladyoflourdescollege7@gmail.com', 'Our Lady of Lourdes College');
                $mail->addAddress($user['email']); 

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset';
                $mail->Body = "Click the following link to reset your password: $reset_link";

                $mail->send();
                header("Location: forgot_password.php?message=" . urlencode("Reset password link has been sent."));
            } catch (Exception $e) {
                echo "Error sending email: " . $mail->ErrorInfo;
            }
        } else {
            echo "Error generating token: " . $conn->error;
        }
    } else {
        echo "User not found. Please check your input.";
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
                <div id="messageContainer" class="text-md text-red-500 mb-2"></div>
                <div class="justify-center items-center inline-flex gap-1">
                    <div><img src="../../assets/svg/ollcLogoNoName.svg" class="w-[56px]" alt="OLLC Logo"></div>
                    <div class="text-2xl font-medium">INFORMATION SYSTEM</div>
                </div>
                <div class="my-4">
                    <form action="" method="post">
                        <div class='text-xl text-center font-medium'>Forgot Password</div>
                        <div class='text-md py-2 font-medium'>Student Number or Email Address:</div>
                        <div class='text-sm p-1 border border-blue-200 rounded-md'><input type="text" id='student_number_or_email' name='student_number_or_email' class='w-full p-1' placeholder='Enter your Student Number or Email Address'/></div>
                        <div class="w-full inline-flex justify-center">
                            <button type="submit" name="submit" value="Submit" class="bg-blue-400 mt-2 py-2 px-8 shadow justify-center items-center inline-flex gap-2 text-white rounded-full hover:bg-blue-600 hover:font-semibold">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
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
