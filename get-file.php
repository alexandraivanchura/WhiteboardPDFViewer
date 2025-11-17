<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust for production

function loadRoomData() {
    try {
        $jsonData = file_get_contents('./RoomData.json');
        if ($jsonData === false) {
            throw new Exception('Failed to load room data');
        }
        
        $roomData = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON format');
        }
        
        return $roomData;
    } catch (Exception $error) {
        error_log('Error loading room data: ' . $error->getMessage());
        return null;
    }
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $roomData = loadRoomData();
    
    if ($roomData) {
        echo json_encode([
            'success' => true,
            'data' => $roomData
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to load room data'
        ]);
    }
}
?>