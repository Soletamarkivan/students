<?php 

include '../php/conn.php';

$query = "SELECT * FROM notifications ORDER BY datetime DESC";  // Order by datetime in descending order
$result = mysqli_query($conn, $query);

if ($result) {
    $notifications = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = array(
            'message' => htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8'),
            'datetime' => $row['datetime']
        );
    }

    mysqli_free_result($result);
} else {
    echo 'Error: ' . mysqli_error($conn);
}

mysqli_close($conn);

function formatDateHeading($datetime)
{
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $date = date('Y-m-d', strtotime($datetime));
    $time = date('g:i a', strtotime($datetime));

    if ($date === $today) {
        return 'Today, ' . $time;
    } elseif ($date === $yesterday) {
        return 'Yesterday, ' . $time;
    } else {
        return date('F j, Y, g:i a', strtotime($datetime));
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <title>Topbar</title>
    
</head>

<body class="bg-gray-100">

    <?php
    date_default_timezone_set('Asia/Manila');
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
    $formattedUsername = ucfirst($username);

    function getGreeting()
    {
        $currentTime = date('H:i:s');
        $morningStart = '06:00:00';
        $afternoonStart = '12:00:00';
        $eveningStart = '18:00:00';

        if ($currentTime >= $morningStart && $currentTime < $afternoonStart) {
            return 'Good Morning!';
        } elseif ($currentTime >= $afternoonStart && $currentTime < $eveningStart) {
            return 'Good Afternoon!';
        } else {
            return 'Good Evening!';
        }
    }

    if (!empty($formattedUsername)) {
    ?>
        <div class="flex justify-between items-center max-w-screen pl-4">
            <div class="flex items-center gap-2">
                <div class="text-xl font-semibold">
                    <?php echo htmlspecialchars(getGreeting(), ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>


            <div class="flex items-center gap-4 relative">
                <div>
                    <?php include './notification.php' ?>
                </div>

                <img src="../assets/svg/profile.svg" alt="Profile Picture" class="w-8 h-8 rounded-full cursor-pointer" id="profileImage">
                <div class="relative group">
                    <button class="text-base font-semibold focus:outline-none">
                        <?php echo htmlspecialchars($formattedUsername, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <div class="absolute hidden bg-white border shadow-md p-2 mt-2 rounded transform -translate-x-full group-hover:block max-w-xs ml-auto">
                        <a href="changepassword.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200" style="white-space: nowrap;">Change password</a>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    <script src="../assets/js/dropdown.js"></script>
    <script>
        function toggleNotifications() {
            var notificationsContainer = document.getElementById('notifications-container');

            if (notificationsContainer.style.display === 'none' || notificationsContainer.style.display === '') {
                notificationsContainer.style.display = 'block';
            } else {
                notificationsContainer.style.display = 'none';
            }
        }
    </script>
</body>

</html>