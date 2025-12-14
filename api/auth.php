<?php
function login($pdo) {
    $email = trim($_POST['email'] ?? '');

    if (!$email) {
        echo json_encode(["success" => false, "message" => "Email required"]);
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(["success" => true, "user" => $user]);
    } else {
        echo json_encode(["success" => false, "message" => "User not found"]);
    }
}
