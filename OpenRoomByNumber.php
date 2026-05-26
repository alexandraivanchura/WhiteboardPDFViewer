<?php
// Extract number from URL path (e.g., /index.php/42)
$path_info = $_SERVER['PATH_INFO'] ?? '';
$number = ltrim($path_info, '/');

// If a numeric value is present in the URL, display it
if ($number !== '' && is_numeric($number)) {
    setcookie('RoomNumber', $number, time() + 86400, '/');
    header('Location: ../PDFViewer.HTML');
    exit;
}
// Otherwise, show the form (no number in URL)
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