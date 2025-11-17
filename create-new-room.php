<?php
header('Content-Type: application/json');

$uploadDir = __DIR__ . '/Uploads/';

// Создать папку для загрузок, если её нет
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (empty($_FILES['pdfFile'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Файл не получен']);
    exit;
}

$file = $_FILES['pdfFile'];

// Проверка типа файла
$allowedTypes = ['application/pdf'];
if (!in_array($file['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Только PDF-файлы разрешены']);
    exit;
}

// Работа с RoomData.json
$roomDataFile = __DIR__ . '/RoomData.json';

// Если файл не существует, создаем базовую структуру
if (!file_exists($roomDataFile)) {
    $initialData = ['Rooms' => []];
    file_put_contents($roomDataFile, json_encode($initialData, JSON_PRETTY_PRINT));
}

// Читаем текущие данные
$currentData = json_decode(file_get_contents($roomDataFile), true);
if ($currentData === null) {
    $currentData = ['Rooms' => []];
}

// Генерация имени файла
$roomNumber = count($currentData['Rooms']) + 1;
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = $roomNumber . '.' . $extension; // Используем номер комнаты как имя файла
$destination = $uploadDir . $filename;

// Перемещение файла
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка загрузки файла']);
    exit;
}


// Создаем новую запись комнаты
$newRoom = [
    'Number' => (string)$roomNumber,
    'PageValue' => 1,
    'PDFLink' => './Uploads/' . $filename,
    'Date' => date('m.d.Y') // Текущая дата в формате MM.DD.YYYY
];

// Проверяем, существует ли уже комната с таким номером
$roomExists = false;
foreach ($currentData['Rooms'] as &$room) {
    if ($room['Number'] === $roomNumber) {
        // Обновляем существующую комнату
        $room = $newRoom;
        $roomExists = true;
        break;
    }
}

// Если комната не существует, добавляем новую
if (!$roomExists) {
    $currentData['Rooms'][] = $newRoom;
}

// Сохраняем обновленные данные
if (file_put_contents($roomDataFile, json_encode($currentData, JSON_PRETTY_PRINT))) {
    echo json_encode([
        'success' => true,
        'message' => 'Файл загружен и комната добавлена/обновлена',
        'room' => $newRoom
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка записи в RoomData.json']);
}
?>