<?php

include '../../php/conn.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $student_number = $_POST["student_number"];
    $password = $_POST["password"];

    $student_number = mysqli_real_escape_string($conn, $student_number);
    $password = mysqli_real_escape_string($conn, $password);

    $sql = "SELECT sn.*, si.status
        FROM student_number sn 
        JOIN school_account sa ON sn.student_number_id = sa.student_number_id
        JOIN student_information si ON sa.school_account_id = si.school_account_id
        WHERE sn.student_number = '$student_number' AND sa.password = '$password'";

    $result = $conn->query($sql);

    if ($result === false) {
        echo "Error: " . $conn->error;
    } else {
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $_SESSION['student_id'] = $row['students_id'] ?? null;
            $_SESSION['student_number'] = $row['student_number'];
            $_SESSION['first_name'] = $row['first_name'] ?? null;
            $_SESSION['surname'] = $row['surname'] ?? null;
            $_SESSION['status'] = $row['status'];

            switch ($row['status']) {
                case 'Pre-registered':
                    header("Location: ../../student/admission.php");
                    break;

                case 'Registered':
                    header("Location: ../../student/enrollment.php");
                    break;

                case 'Enlisted':
                    header("Location: ../../student/paymentReference.php");
                    break;
                    
                case 'Official Enrolled':
                    header("Location: ../../student/dashboard.php");
                    break;

                case 'Not-enrolled':
                    header("Location: ../../student/enrollment.php");
                    break;

                default:
                    echo "Invalid student status";
                    break;
            }
            exit();
        } else {
            $error_message = "Invalid Credentials";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body class="font-[roboto-serif]">
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
                            <div class='text-xl text-center font-medium'>STUDENT LOGIN</div>
                            <div class='text-lg py-2 font-medium'>Student Number:</div>
                            <div class='text-lg p-1 border border-blue-200 rounded-md'><input type="text" id='student_number' name='student_number' autoComplete='none' class='w-full p-1' placeholder='Enter your Username'/></div>
                            <div class='text-lg py-2 font-medium'>Password:</div>
                            <div class='flex items-center text-lg p-1 border border-blue-200 rounded-md'>
                                <input type="password" id='password' name='password' autoComplete='none' class='w-full p-1' placeholder='Enter your Password'>  
                                <span toggle="#password" class="password-toggle cursor-pointer">
                                    <svg class="h-6 w-6" fill="none" stroke="gray" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2 12s3-6 10-6 10 6 10 6"></path>
                                    </svg>
                                </span>
                            </input>
                                
                            </div>
                            
                            <?php if (!empty($error_message)) : ?>
                                <div class="text-red-500 text-sm mt-2"><?php echo $error_message; ?></div>
                            <?php endif; ?>
                            <a href="./forgot_password.php" class="hover:underline text-red-700 my-2">Forgot my password</a>
                            <div class="w-full inline-flex justify-center">
                                <button type="submit" name="submit" value="Login" class="bg-blue-400 mt-2 py-2 px-8 shadow justify-center items-center inline-flex gap-2 text-white rounded-full hover:bg-blue-600 hover:font-semibold">Login</button>
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
</body>
</html>
