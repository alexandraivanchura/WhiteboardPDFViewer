<?php
// update-room.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow requests from your HTML file
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Get JSON input from the request
$input = json_decode(file_get_contents('php://input'), true);
$roomNumber = $input['roomNumber'] ?? '';
$newPageValue = $input['newPageValue'] ?? '';

// Validate input
if (empty($roomNumber) || empty($newPageValue)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Room number and new page value are required'
    ]);
    exit;
}

// Your JSON file path (same directory)
$jsonFile = __DIR__ . '/RoomData.json'; // Replace with your actual JSON filename

// Check if file exists and is writable
if (!file_exists($jsonFile)) {
    http_response_code(404);
    echo json_encode([
        'success' => false, 
        'message' => 'JSON file not found: ' . $jsonFile
    ]);
    exit;
}

if (!is_writable($jsonFile)) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'JSON file is not writable'
    ]);
    exit;
}

try {
    // Read current JSON data
    $jsonContent = file_get_contents($jsonFile);
    $data = json_decode($jsonContent, true);
    
    if ($data === null) {
        throw new Exception('Invalid JSON format in file');
    }
    
    // Find and update the room
    $roomFound = false;
    foreach ($data['Rooms'] as &$room) {
        if ($room['Number'] === $roomNumber) {
            $room['PageValue'] = $newPageValue;
            $roomFound = true;
            break;
        }
    }
    
    if (!$roomFound) {
        http_response_code(404);
        echo json_encode([
            'success' => false, 
            'message' => 'Room number ' . $roomNumber . ' not found'
        ]);
        exit;
    }
    
    // Write back to file with pretty print
    $result = file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    if ($result === false) {
        throw new Exception('Failed to write to JSON file');
    }
    
    // Success response
    echo json_encode([
        'success' => true, 
        'message' => 'Room ' . $roomNumber . ' updated to page ' . $newPageValue,
        'updatedData' => $data
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>