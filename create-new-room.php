<?php
header('Content-Type: application/json');

// Максимальный размер загружаемого файла (1 МБ)
//define('MAX_FILE_SIZE', 0.7 * 1024 * 1024);

$uploadDir = __DIR__ . '/Uploads/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (empty($_FILES['pdfFile'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Файл не получен']);
    exit;
}

$file = $_FILES['pdfFile'];

// 1. Проверка ошибок загрузки
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'Файл превышает максимальный размер, установленный сервером (php.ini)',
        UPLOAD_ERR_FORM_SIZE  => 'Файл превышает максимальный размер, указанный в HTML-форме',
        UPLOAD_ERR_PARTIAL    => 'Файл был загружен частично',
        UPLOAD_ERR_NO_FILE    => 'Файл не был загружен',
        UPLOAD_ERR_NO_TMP_DIR => 'Временная папка отсутствует',
        UPLOAD_ERR_CANT_WRITE => 'Ошибка записи файла на диск',
        UPLOAD_ERR_EXTENSION  => 'Загрузка файла остановлена PHP-расширением',
    ];
    $message = isset($errorMessages[$file['error']]) 
                ? $errorMessages[$file['error']] 
                : 'Неизвестная ошибка загрузки';
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// // 2. Проверка размера файла (собственный лимит)
// if ($file['size'] > MAX_FILE_SIZE) {
//     http_response_code(413); // Payload Too Large
//     echo json_encode([
//         'success' => false,
//         'message' => 'Файл слишком большой. Максимальный размер: ' . (MAX_FILE_SIZE / 1024 / 1024) . ' МБ'
//     ]);
//     exit;
// }

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

function cleanupExpiredRooms(&$roomsData, $uploadDir) {
    $currentTimestamp = time();
    $roomsToKeep = [];
    
    foreach ($roomsData['Rooms'] as $room) {
        // Преобразуем дату из формата "m.d.Y" в timestamp
        $expiredDateParts = explode('.', $room['ExpiredDate']);
        if (count($expiredDateParts) === 3) {
            $expiredTimestamp = mktime(0, 0, 0, $expiredDateParts[0], $expiredDateParts[1], $expiredDateParts[2]);
            
            // Если дата истечения меньше или равна текущей
            if ($expiredTimestamp <= $currentTimestamp) {
                $pdfPath = __DIR__ . '/' . ltrim($room['PDFLink'], './');
                if (file_exists($pdfPath)) {
                    unlink($pdfPath);
                }
                continue;
            }
        }
        $roomsToKeep[] = $room;
    }
    
    $roomsData['Rooms'] = $roomsToKeep;
}

// Очищаем просроченные комнаты перед добавлением новой
cleanupExpiredRooms($currentData, $uploadDir);

// Функция для генерации случайного номера комнаты
function generateRandomRoomNumber($existingRooms) {
    $maxAttempts = 100; // Максимальное количество попыток во избежание бесконечного цикла
    $attempts = 0;
    
    while ($attempts < $maxAttempts) {
        // Генерируем случайное число в диапазоне 1000-9999
        $randomNumber = (string)rand(1000, 9999);
        
        // Проверяем, существует ли уже комната с таким номером
        $exists = false;
        foreach ($existingRooms as $room) {
            if ($room['Number'] === $randomNumber) {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            return $randomNumber;
        }
        
        $attempts++;
    }
    
    // Если не удалось сгенерировать уникальный номер, используем временную метку
    return (string)time();
}

$roomNumber = generateRandomRoomNumber($currentData['Rooms']);
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
    'Date' => date('m.d.Y'), // Текущая дата в формате MM.DD.YYYY
    'ExpiredDate' => date('m.d.Y', strtotime('+2 days'))
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