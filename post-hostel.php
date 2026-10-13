<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'], true)) {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}

// Verify that the landlord is authenticated via session or request payload
$landlord_id = $_SESSION['landlord_id'] ?? null;

// Read JSON input body
$input = json_decode(file_get_contents('php://input'), true);

// Fallback to session landlord_id if not in payload, or vice versa
if (!$landlord_id && isset($input['landlord_id'])) {
    $landlord_id = $input['landlord_id'];
}

if (!$landlord_id) {
    echo json_encode(["status" => "error", "message" => "Unauthorized. Please log in first."]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (!isset($_SESSION['landlord_id'])) {
        echo json_encode(["status" => "error", "message" => "Unauthorized. Please log in first."]);
        exit();
    }

    $hostel_id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$hostel_id || $hostel_id < 1) {
        echo json_encode(["status" => "error", "message" => "A valid hostel ID is required."]);
        exit();
    }
}

// Sanitize and extract form fields
$title       = trim($input['title'] ?? '');
$room_type   = trim($input['room_type'] ?? '');
$price       = trim($input['price'] ?? '');
$distance    = trim($input['distance'] ?? '');
$location    = trim($input['location'] ?? '');
$phone       = trim($input['phone'] ?? '');
$image_url   = trim($input['image_url'] ?? '');
$lat         = $input['lat'] ?? null;
$lng         = $input['lng'] ?? null;

if (empty($title) || empty($room_type) || empty($price) || empty($location)) {
    echo json_encode(["status" => "error", "message" => "Please fill in all required fields."]);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $stmt = $pdo->prepare("
            UPDATE hostels
            SET title = :title, room_type = :room_type, price = :price,
                distance = :distance, location = :location, phone = :phone,
                image_url = :image_url, lat = :lat, lng = :lng
            WHERE id = :id AND landlord_id = :landlord_id
        ");

        $stmt->execute([
            'id'          => $hostel_id,
            'landlord_id' => $_SESSION['landlord_id'],
            'title'       => $title,
            'room_type'   => $room_type,
            'price'       => $price,
            'distance'    => $distance,
            'location'    => $location,
            'phone'       => $phone,
            'image_url'   => $image_url,
            'lat'         => $lat,
            'lng'         => $lng
        ]);

        if ($stmt->rowCount() === 0) {
            echo json_encode(["status" => "error", "message" => "Hostel not found or you do not own this listing."]);
            exit();
        }

        echo json_encode(["status" => "success", "message" => "Hostel updated successfully!"]);
        exit();
    }

    $stmt = $pdo->prepare("
        INSERT INTO hostels (landlord_id, title, room_type, price, distance, location, phone, image_url, lat, lng) 
        VALUES (:landlord_id, :title, :room_type, :price, :distance, :location, :phone, :image_url, :lat, :lng)
    ");

    $stmt->execute([
        'landlord_id' => $landlord_id,
        'title'       => $title,
        'room_type'   => $room_type,
        'price'       => $price,
        'distance'    => $distance,
        'location'    => $location,
        'phone'       => $phone,
        'image_url'   => $image_url,
        'lat'         => $lat,
        'lng'         => $lng
    ]);

    echo json_encode(["status" => "success", "message" => "Hostel posted successfully!"]);
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Failed to save hostel: " . $e->getMessage()]);
}
?>