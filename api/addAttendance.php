<?php
header("Content-Type: text/plain; charset=UTF-8");

require_once __DIR__ . "/../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("POST required");
}

$student = trim($_POST["student_name"] ?? "");
$course = trim($_POST["course"] ?? "");
$status = strtoupper(trim($_POST["status"] ?? ""));

if (
    $student === ""
    || $course === ""
    || !in_array($status, ["ENTER", "EXIT"], true)
) {
    http_response_code(400);
    exit("Missing or invalid parameters");
}

$date = date("Y-m-d");
$time = date("H:i:s");

if ($status === "ENTER") {
    $stmt = $conn->prepare(
        "INSERT INTO attendance
         (student_name, course, date, entry_time, status)
         VALUES (?, ?, ?, ?, 'ENTER')"
    );

    if (!$stmt) {
        http_response_code(500);
        exit("Database error");
    }

    $stmt->bind_param("ssss", $student, $course, $date, $time);
} else {
    $stmt = $conn->prepare(
        "UPDATE attendance
         SET exit_time = ?, status = 'EXIT'
         WHERE student_name = ?
           AND course = ?
           AND date = ?
           AND exit_time IS NULL
         ORDER BY id DESC
         LIMIT 1"
    );

    if (!$stmt) {
        http_response_code(500);
        exit("Database error");
    }

    $stmt->bind_param("ssss", $time, $student, $course, $date);
}

if (!$stmt->execute()) {
    http_response_code(500);
    exit("Database error");
}

$stmt->close();

$studentStmt = $conn->prepare(
    "INSERT INTO registered_students (student_name, huskylens_id)
     VALUES (?, 0)
     ON DUPLICATE KEY UPDATE student_name = VALUES(student_name)"
);

if ($studentStmt) {
    $studentStmt->bind_param("s", $student);
    $studentStmt->execute();
    $studentStmt->close();
}

echo "SUCCESS";
$conn->close();
