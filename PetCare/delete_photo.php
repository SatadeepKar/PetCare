<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once 'config.php';
$conn = get_db();

$user_id = $_SESSION['user_id'];

// Check if filename is provided
if (!isset($_POST['filename']) || empty($_POST['filename'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No filename provided']);
    exit();
}

$filename = $_POST['filename'];

// Verify the photo belongs to the current user
$stmt = $conn->prepare("SELECT id FROM pet_photos WHERE user_id = ? AND filename = ?");
$stmt->bind_param("is", $user_id, $filename);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Photo not found or not owned by current user']);
    $stmt->close();
    $conn->close();
    exit();
}

// Delete the photo from the database
$stmt = $conn->prepare("DELETE FROM pet_photos WHERE user_id = ? AND filename = ?");
$stmt->bind_param("is", $user_id, $filename);
$success = $stmt->execute();

if ($success) {
    // Delete the file from the server
    $file_path = "Uploads/" . $filename;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
