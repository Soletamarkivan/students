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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div>
        <div id="notifications-container" class="bg-white shadow-lg hidden fixed top-16 right-24 max-h-3/4 overflow-y-scroll max-w-sm p-4 rounded-md border border-blue-300">
            <div class="tex-base font-semibold">Notifications</div>
            <?php if (!empty($notifications)) : ?>
                <?php foreach ($notifications as $notification) : ?>
                    <div><?= $notification['message']; ?></div>
                    <div class="text-xs text-gray-500"><?= date('F j, Y, g:i a', strtotime($notification['datetime'])); ?></div>
                    <hr class="my-2">
                <?php endforeach; ?>
            <?php else : ?>
                <div>No new notifications</div>
            <?php endif; ?>
        </div>
        <button id="notification-button" class="flex items-center" type="button" onclick="toggleNotifications()">
            <span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" onmouseover="this.style.fill='#1d4ed8';" onmouseout="this.style.fill='#fff';" style="stroke: #1d4ed8;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
            </span>
            <span class="absolute -mt-4 ml-4 rounded-full p-1 text-xs font-medium leading-none notification-counter"><?= count($notifications); ?></span>
        </button>
    </div>
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
