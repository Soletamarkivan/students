<?php

include 'conn.php';
date_default_timezone_set('Asia/Manila');

require '../vendor/autoload.php'; 

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    error_log(json_encode($_POST));
    $course = (int)$_POST["course"];
    $school_year = $_POST["schoolYear"];
    $surname = $_POST["stdSurname"];
    $first_name = $_POST["stdFirstname"];
    $middle_name = $_POST["stdMiddlename"];
    $suffix = $_POST["stdSuffix"];
    $city = $_POST["stdCity"];
    $email = $_POST["email"];
    $mobile = $_POST["stdMobile"];

    $checkExistingUser = "SELECT student_id FROM students WHERE first_name = '$first_name' AND surname = '$surname'";
    $resultExistingUser = $conn->query($checkExistingUser);

    if ($resultExistingUser->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "User with the same name already exists"]);
        exit;
    }

    $firstLetterFirstName = ord(strtoupper(substr($first_name, 0, 1))) - ord('A') + 1;
    $firstLetterSurname = ord(strtoupper(substr($surname, 0, 1))) - ord('A') + 1;

    $currentDate = date("Ymd"); 

    $studentNumber = strtoupper($firstLetterFirstName . $currentDate . date("His") . $firstLetterSurname);

    $password = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);

    $verificationCode = md5(uniqid(rand(), true));

    /** @var \PHPMailer\PHPMailer\PHPMailer $mail */
    $mail = new \PHPMailer\PHPMailer\PHPMailer;

    //$mail->SMTPDebug = 2;  
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->Port = 587; 
    $mail->SMTPAuth = true;
    $mail->Username = 'ourladyoflourdescollege7@gmail.com'; 
    $mail->Password = 'lbue japy zvvp eyct'; 
    $mail->setFrom('ourladyoflourdescollege7@gmail.com', 'Our Lady of Lourdes College');
    $mail->addAddress($email, $first_name . ' ' . $surname);
    $mail->Subject = 'Email Verification';
    $mail->Body = "Please click the following link to verify your email: http://localhost/student-portal/php/verify.php?code=$verificationCode";

    if (!$mail->send()) {
        echo json_encode(["status" => "error", "message" => "Error sending verification email: " . $mail->ErrorInfo]);
        exit;
    }

    $sqlVerificationCode = "INSERT INTO email_verification (email, code) VALUES ('$email', '$verificationCode')";
    if ($conn->query($sqlVerificationCode) !== TRUE) {
        echo json_encode(["status" => "error", "message" => "Error inserting verification code: " . $conn->error]);
        exit;
    }

    $notificationMessage = "$first_name $surname filled up a pre-registration form";
    $notificationDatetime = date('Y-m-d H:i:s');

    $sqlNotification = "INSERT INTO notifications (message, datetime) VALUES ('$notificationMessage', '$notificationDatetime')";
    if ($conn->query($sqlNotification) !== TRUE) {
        echo json_encode(["status" => "error", "message" => "Error inserting notification: " . $conn->error]);
        exit;
    }

    $sqlStudentNumber = "INSERT INTO student_number (student_number) VALUES ('$studentNumber')";
    if ($conn->query($sqlStudentNumber) === TRUE) {
        $studentNumberId = $conn->insert_id;

        $sqlEnrollmentDetails = "INSERT INTO enrollment_details (course_id, school_year) VALUES ('$course', '$school_year')";
        if ($conn->query($sqlEnrollmentDetails) === TRUE) {
            $enrollmentDetailsId = $conn->insert_id;

            $sqlContactInformation = "INSERT INTO contact_information (city, email, mobile_number) VALUES ('$city','$email','$mobile')";
            if ($conn->query($sqlContactInformation) === TRUE) {
                $contactInformationId = $conn->insert_id;

                $sqlStudent = "INSERT INTO students (student_number_id, surname, first_name, middle_name, suffix) VALUES ('$studentNumberId','$surname','$first_name','$middle_name','$suffix')";
                if ($conn->query($sqlStudent) === TRUE) {
                    $studentId = $conn->insert_id;

                    $sqlSchoolAccount = "INSERT INTO school_account (student_number_id, password) VALUES ('$studentNumberId', '$password')";
                    if ($conn->query($sqlSchoolAccount) === TRUE) {
                        $schoolAccountId = $conn->insert_id;

                        $sqlStudentInformation = "INSERT INTO student_information (student_id, contact_information_id, enrollment_details_id, school_account_id, status) VALUES ('$studentId','$contactInformationId', '$enrollmentDetailsId', '$schoolAccountId','Pre-registered')";
                        if ($conn->query($sqlStudentInformation) === TRUE) {
                            header("Location: ../pre-registration.html?message=" . urlencode("Please check your email to verify."));
                        } else {
                            echo json_encode(["status" => "error", "message" => "Error inserting into student information"]);
                        }
                    } else {
                        echo json_encode(["status" => "error", "message" => "Error inserting into school account: " . $conn->error]);
                    }
                } else {
                    echo json_encode(["status" => "error", "message" => "Error inserting into students: " . $conn->error]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Error inserting into contact information: " . $conn->error]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Error inserting into enrollment details: " . $conn->error]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Error inserting into student number: " . $conn->error]);
        exit;
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
}

?>
