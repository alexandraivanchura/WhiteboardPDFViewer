<?php
// Extract number from URL path (e.g., /index.php/42)
$path_info = $_SERVER['PATH_INFO'] ?? '';
$number = ltrim($path_info, '/');

$roomDataFile = __DIR__ . '/RoomData.json';

// Читаем текущие данные
$currentData = json_decode(file_get_contents($roomDataFile), true);
if ($currentData === null) {
    $currentData = ['Rooms' => []];
}
$existingRooms = $currentData['Rooms'];

$exists = false;
foreach ($existingRooms as $room) {
    if ($room['Number'] === $number) {
        $exists = true;
        break;
    }
}

if (!$exists) {
    header('Location: ../OpenRoom.HTML');
    exit;
}

if ($number !== '' && is_numeric($number)) {
    setcookie('RoomNumber', $number, time() + 86400, '/');
    header('Location: ../PDFViewer.HTML');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enter a number – cookie + redirect</title>
</head>
<body>
</body>
</html>