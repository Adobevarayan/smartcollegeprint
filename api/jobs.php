<?php

/* ===========================
   GET ALL JOBS FOR A USER
=========================== */
function getJobs($pdo)
{
    $userId = $_GET['userId'] ?? null;

    if (!$userId) {
        echo json_encode([]);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT 
            pj.id,
            pj.file_name,
            pj.pages,
            pj.copies,
            pj.status,
            pj.cost,
            pj.created_at,
            pr.name AS printer_name
        FROM print_jobs pj
        JOIN printers pr ON pj.printer_id = pr.id
        WHERE pj.user_id = ?
        ORDER BY pj.created_at DESC
    ");

    $stmt->execute([$userId]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($jobs);
}


/* ===========================
   SUBMIT A NEW PRINT JOB
=========================== */
function submitJob($pdo)
{
    $userId     = $_POST['userId'] ?? null;
    $fileName   = trim($_POST['fileName'] ?? '');
    $pages      = (int)($_POST['pages'] ?? 0);
    $copies     = (int)($_POST['copies'] ?? 0);
    $colorMode  = $_POST['colorMode'] ?? 'bw';
    $paperSize  = $_POST['paperSize'] ?? 'A4';
    $printerId  = (int)($_POST['printerId'] ?? 0);
    $cost       = (float)($_POST['cost'] ?? 0);

    // ---------- Basic Validation ----------
    if (
        !$userId || !$fileName || $pages <= 0 ||
        $copies <= 0 || !$printerId || $cost <= 0
    ) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid print job data"
        ]);
        return;
    }

    // ---------- Check User Balance ----------
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            "success" => false,
            "message" => "User not found"
        ]);
        return;
    }

    if ($user['balance'] < $cost) {
        echo json_encode([
            "success" => false,
            "message" => "Insufficient balance"
        ]);
        return;
    }

    // ---------- Insert Print Job ----------
    $stmt = $pdo->prepare("
        INSERT INTO print_jobs 
        (user_id, file_name, pages, copies, color_mode, paper_size, printer_id, cost)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $fileName,
        $pages,
        $copies,
        $colorMode,
        $paperSize,
        $printerId,
        $cost
    ]);

    // ---------- Deduct Balance ----------
    $stmt = $pdo->prepare("
        UPDATE users 
        SET balance = balance - ? 
        WHERE id = ?
    ");
    $stmt->execute([$cost, $userId]);

    echo json_encode([
        "success" => true,
        "message" => "Print job submitted successfully"
    ]);
}
