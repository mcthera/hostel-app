<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? $_POST['username'] ?? '');
$password = trim($input['password'] ?? $_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Username and password are required."]);
    exit();
}

try {
    // Fetch landlord record from Neon
    $stmt = $pdo->prepare("SELECT * FROM landlords WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $landlord = $stmt->fetch();

    if ($landlord && password_verify($password, $landlord['password_hash'])) {
        // Start session for the landlord
        $_SESSION['landlord_id'] = $landlord['id'];
        $_SESSION['username'] = $landlord['username'];

        echo json_encode([
            "status" => "success", 
            "message" => "Login successful!",
            "landlord_id" => $landlord['id'],
            "username" => $landlord['username']
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid username or password."]);
    }
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Login failed: " . $e->getMessage()]);
}
?>