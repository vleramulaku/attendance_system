<?php

date_default_timezone_set("Europe/Belgrade");

$conn = new mysqli("localhost", "root", "", "attendance_system");

if ($conn->connect_error) {
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");
