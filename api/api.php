<?php
// api.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST");

require_once "db.php";

$action = $_GET['action'] ?? '';

function respond($data) {
    echo json_encode($data);
    exit;
}

// ---------- LOGIN ----------
if ($action === 'login') {
    $email = $_POST['email'] ?? '';

    $stmt = $conn->prepare("SELECT id, email, name, role, balance, department FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if ($user) {
        respond(['success' => true, 'user' => $user]);
    } else {
        respond(['success' => false, 'message' => 'User not found']);
    }
}

// ---------- REGISTER ----------
if ($action === 'register') {
    $name       = $_POST['name'] ?? '';
    $email      = $_POST['email'] ?? '';
    $department = $_POST['department'] ?? '';

    // check existing
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        respond(['success' => false, 'message' => 'Email already exists']);
    }

    $role    = 'student';
    $balance = 25.00;

    $stmt = $conn->prepare("INSERT INTO users (email, name, role, balance, department)
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssds", $email, $name, $role, $balance, $department);
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        respond([
            'success' => true,
            'message' => 'Account created',
            'user' => [
                'id' => $id,
                'email' => $email,
                'name' => $name,
                'role' => $role,
                'balance' => $balance,
                'department' => $department
            ]
        ]);
    } else {
        respond(['success' => false, 'message' => 'Error creating user']);
    }
}

// ---------- GET PRINTERS ----------
if ($action === 'printers') {
    $result = $conn->query("SELECT * FROM printers");
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    respond($rows);
}

// ---------- GET USER PRINT JOBS ----------
if ($action === 'jobs') {
    $userId = intval($_GET['userId'] ?? 0);
    $stmt = $conn->prepare("SELECT pj.*, p.name AS printer_name
                            FROM print_jobs pj
                            JOIN printers p ON pj.printer_id = p.id
                            WHERE pj.user_id = ?
                            ORDER BY pj.created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    respond($rows);
}

// ---------- SUBMIT PRINT JOB ----------
if ($action === 'submitJob') {
    $userId     = intval($_POST['userId'] ?? 0);
    $fileName   = $_POST['fileName'] ?? '';
    $pages      = intval($_POST['pages'] ?? 0);
    $copies     = intval($_POST['copies'] ?? 1);
    $colorMode  = $_POST['colorMode'] ?? 'bw';
    $paperSize  = $_POST['paperSize'] ?? 'A4';
    $printerId  = intval($_POST['printerId'] ?? 0);
    $cost       = floatval($_POST['cost'] ?? 0);

    // Insert job
    $stmt = $conn->prepare("INSERT INTO print_jobs
        (user_id, file_name, pages, copies, color_mode, paper_size, printer_id, status, cost)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
    $stmt->bind_param("isiissid",
        $userId, $fileName, $pages, $copies, $colorMode, $paperSize, $printerId, $cost);

    if ($stmt->execute()) {
        // Update balance
        $stmt2 = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt2->bind_param("di", $cost, $userId);
        $stmt2->execute();

        respond(['success' => true, 'message' => 'Print job submitted']);
    } else {
        respond(['success' => false, 'message' => 'Error submitting job']);
    }
}

respond(['success' => false, 'message' => 'Unknown action']);
