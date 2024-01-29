<?php

require '../vendor/autoload.php';

header('Content-Type: application/json');

include 'conn.php';
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["code"])) {
    $verificationCode = $_GET["code"];

    $checkVerificationCode = "SELECT * FROM email_verification WHERE code = '$verificationCode'";
    $resultVerificationCode = $conn->query($checkVerificationCode);

    if ($resultVerificationCode === false) {
        echo "Error executing verification code query: " . $conn->error;
        exit;
    }

    if ($resultVerificationCode->num_rows > 0) {
        $row = $resultVerificationCode->fetch_assoc();
        $email = $row["email"];

        $getStudentInfo = "SELECT sn.student_number, sa.password
                    FROM students s
                    JOIN student_number sn ON s.student_number_id = sn.student_number_id
                    JOIN school_account sa ON sn.student_number_id = sa.student_number_id
                    JOIN student_information si ON s.student_id = si.student_id
                    JOIN contact_information ci ON si.contact_information_id = ci.contact_information_id
                    WHERE ci.email = '$email'";
        $resultStudentInfo = $conn->query($getStudentInfo);

        if ($resultStudentInfo === false) {
            echo "Error executing student information query: " . $conn->error;
            exit;
        }

        if ($resultStudentInfo->num_rows > 0) {
            $studentInfo = $resultStudentInfo->fetch_assoc();

            $mail = new PHPMailer\PHPMailer\PHPMailer;

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 587; 
            $mail->SMTPAuth = true;
            $mail->Username = 'ourladyoflourdescollege7@gmail.com'; 
            $mail->Password = 'lbue japy zvvp eyct'; 
            $mail->setFrom('ourladyoflourdescollege7@gmail.com', 'Our Lady of Lourdes College');
            $mail->addAddress($email);
            $mail->Subject = 'Student Number and Password';
            $mail->Body = "Your Student Number: {$studentInfo['student_number']}\nYour Password: {$studentInfo['password']}";

            if ($mail->send()) {
                header("Location: ../login/student/login.php?message=" . urlencode("Login your credential we've sent to your email."));
            } else {
                echo "Error sending email: " . $mail->ErrorInfo;
            }
        } else {
            echo "No student information found for the given email.";
        }

        $deleteVerificationCode = "DELETE FROM email_verification WHERE code = '$verificationCode'";
        if ($conn->query($deleteVerificationCode) === false) {
            echo "Error deleting verification code: " . $conn->error;
        }
    } else {
        echo "Invalid verification code.";
    }
} else {
    echo "Invalid request.";
}
?>
