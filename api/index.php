<?php
header("Content-Type: application/json");
require "db.php";

$action = $_GET['action'] ?? '';

switch ($action) {
    case "login":
        require "auth.php";
        login($pdo);
        break;

    case "register":
        require "auth.php";
        register($pdo);
        break;

    case "printers":
        require "printers.php";
        getPrinters($pdo);
        break;

    case "jobs":
        require "jobs.php";
        getJobs($pdo);
        break;

    case "submitJob":
        require "jobs.php";
        submitJob($pdo);
        break;

    default:
        echo json_encode(["error" => "Invalid API action"]);
}
