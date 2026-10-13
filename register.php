<?php
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit();
}

// Get POST data (supports both JSON payloads and standard form data)
$input = json_decode(file_get_contents('php://input'), true);
$username     = trim($input['username'] ?? $_POST['username'] ?? '');
$password     = trim($input['password'] ?? $_POST['password'] ?? '');
$phone_number = trim($input['phone_number'] ?? $_POST['phone_number'] ?? '');

if (empty($username) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Username and password are required."]);
    exit();
}

try {
    // Check if username already exists in Neon
    $checkStmt = $pdo->prepare("SELECT id FROM landlords WHERE username = :username");
    $checkStmt->execute(['username' => $username]);

    if ($checkStmt->fetch()) {
        echo json_encode(["status" => "error", "message" => "Username is already taken."]);
        exit();
    }

    // Hash password securely
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Insert new landlord record
    $insertStmt = $pdo->prepare("INSERT INTO landlords (username, password_hash, phone_number) VALUES (:username, :hash, :phone)");
    $insertStmt->execute([
        'username' => $username,
        'hash'     => $password_hash,
        'phone'    => $phone_number
    ]);

    echo json_encode(["status" => "success", "message" => "Landlord registered successfully!"]);
} catch (\PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Registration failed: " . $e->getMessage()]);
}
?>