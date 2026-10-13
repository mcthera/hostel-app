<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$hostel_id   = $input['id'] ?? null;
$landlord_id = $_SESSION['landlord_id'] ?? $input['landlord_id'] ?? null;

if (!$hostel_id || !$landlord_id) {
    echo json_encode(["status" => "error", "message" => "Missing hostel ID or authentication."]);
    exit();
}

// Sanitize inputs
$title     = trim($input['title'] ?? '');
$room_type = trim($input['room_type'] ?? '');
$price     = trim($input['price'] ?? '');
$distance  = trim($input['distance'] ?? '');
$location  = trim($input['location'] ?? '');
$phone     = trim($input['phone'] ?? '');

if (empty($title) || empty($price) || empty($location)) {
    echo json_encode(["status" => "error", "message" => "Title, price, and location are required."]);
    exit();
}

try {
    // Verify that this hostel belongs to the logged-in landlord before updating
    $checkStmt = $pdo->prepare("SELECT id FROM hostels WHERE id = :id AND landlord_id = :landlord_id");
    $checkStmt->execute(['id' => $hostel_id, 'landlord_id' => $landlord_id]);
    
    if (!$checkStmt->fetch()) {
        echo json_encode(["status" => "error", "message" => "Unauthorized or hostel not found."]);
        exit();
    }

    // Update the hostel record in Neon
    $updateStmt = $pdo->prepare("
        UPDATE hostels 
        SET title = :title, 
            room_type = :room_type, 
            price = :price, 
            distance = :distance, 
            location = :location, 
            phone = :phone
        WHERE id = :id AND landlord_id = :landlord_id
    ");

    $updateStmt->execute([
        'title'       => $title,
        'room_type'   => $room_type,
        'price'       => $price,
        'distance'    => $distance,
        'location'    => $location,
        'phone'       => $phone,
        'id'          => $hostel_id,
        'landlord_id' => $landlord_id
    ]);

    echo json_encode(["status" => "success", "message" => "Hostel updated successfully!"]);
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Update failed: " . $e->getMessage()]);
}
?>