<?php
header("Content-Type: text/plain; charset=UTF-8");

require_once __DIR__ . "/../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("POST required");
}

$professor = trim($_POST["professor_name"] ?? "");
$course = trim($_POST["course"] ?? "");
$action = strtoupper(trim($_POST["action"] ?? ""));

if (
    $professor === ""
    || $course === ""
    || !in_array($action, ["START", "END"], true)
) {
    http_response_code(400);
    exit("Missing or invalid parameters");
}

$date = date("Y-m-d");
$time = date("H:i:s");

if ($action === "START") {
    $stmt = $conn->prepare(
        "INSERT INTO course_sessions
         (professor_name, course, session_date, start_time, status)
         VALUES (?, ?, ?, ?, 'ACTIVE')"
    );

    if (!$stmt) {
        http_response_code(500);
        exit("Database setup incomplete");
    }

    $stmt->bind_param("ssss", $professor, $course, $date, $time);
} else {
    $stmt = $conn->prepare(
        "UPDATE course_sessions
         SET end_time = ?, status = 'ENDED'
         WHERE professor_name = ?
           AND course = ?
           AND session_date = ?
           AND end_time IS NULL
         ORDER BY id DESC
         LIMIT 1"
    );

    if (!$stmt) {
        http_response_code(500);
        exit("Database setup incomplete");
    }

    $stmt->bind_param("ssss", $time, $professor, $course, $date);
}

if (!$stmt->execute()) {
    http_response_code(500);
    exit("Database error");
}

if ($action === "END" && $stmt->affected_rows === 0) {
    $stmt->close();
    $conn->close();
    exit("NO ACTIVE SESSION");
}

$stmt->close();

if ($action === "START") {
    $syncStmt = $conn->prepare(
        "INSERT INTO professors (professor_name, course, huskylens_id)
         SELECT ?, ?, 0
         WHERE NOT EXISTS (
             SELECT 1
             FROM professors
             WHERE professor_name = ?
               AND course = ?
         )"
    );

    if ($syncStmt) {
        $syncStmt->bind_param(
            "ssss",
            $professor,
            $course,
            $professor,
            $course
        );
        $syncStmt->execute();
        $syncStmt->close();
    }
}

echo "SUCCESS";
$conn->close();
