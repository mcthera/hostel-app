<?php
header('Content-Type: application/json');
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $landlord_id = $_GET['landlord_id'] ?? null;

        if ($landlord_id) {
            $stmt = $pdo->prepare("SELECT * FROM hostels WHERE landlord_id = :landlord_id ORDER BY created_at DESC");
            $stmt->execute(['landlord_id' => $landlord_id]);
        } else {
            $stmt = $pdo->query("SELECT * FROM hostels ORDER BY created_at DESC");
        }

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo json_encode(["error" => "Failed to fetch hostels: " . $e->getMessage()]);
    }
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if ($data) {
        $sql = "INSERT INTO hostels (landlord_id, title, room_type, price, distance, location, phone, image_url, lat, lng) 
                VALUES (:landlord_id, :title, :room_type, :price, :distance, :location, :phone, :image_url, :lat, :lng)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':landlord_id' => 1,
            ':title' => $data['title'],
            ':room_type' => $data['roomType'],
            ':price' => $data['price'],
            ':distance' => $data['distance'],
            ':location' => $data['location'],
            ':phone' => $data['phone'],
            ':image_url' => $data['image'],
            ':lat' => 6.6920 + (rand(-10, 10) / 1000),
            ':lng' => -1.6160 + (rand(-10, 10) / 1000)
        ]);
        echo json_encode(["success" => true, "message" => "Hostel published to PostgreSQL successfully!"]);
    }
}

if ($method === 'DELETE' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM hostels WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(["success" => true]);
}
?>