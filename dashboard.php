<?php
include("includes/db.php");

$today = date("Y-m-d");

function getTodayCount($conn, $sql, $today)
{
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return (int)($row["total"] ?? 0);
}
$studentsToday = getTodayCount(
    $conn,
    "SELECT COUNT(DISTINCT student_name) AS total
     FROM attendance
     WHERE date = ?",
    $today
);

$professorsToday = 0;
$coursesToday = 0;

$sessionTable = $conn->query("SHOW TABLES LIKE 'course_sessions'");

if ($sessionTable && $sessionTable->num_rows > 0) {
    $professorsToday = getTodayCount(
        $conn,
        "SELECT COUNT(DISTINCT professor_name) AS total
         FROM course_sessions
         WHERE session_date = ?",
        $today
    );
    $coursesToday = getTodayCount(
        $conn,
        "SELECT COUNT(*) AS total
         FROM course_sessions
         WHERE session_date = ?",
        $today
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<header>
    <h1>Attendance Management System</h1>
    <?php include("includes/navbar.php"); ?>
</header>

<main class="container">
    <section class="dashboard-panel">
        <div class="page-heading">
            <h2>Dashboard</h2>
        </div>

        <div class="cards">
            <article class="metric-card">
                <div class="date-value" id="current-date">
                    <?php echo date("l, d F Y"); ?>
                </div>

                <div
                    class="metric-value time-value"
                    id="current-time"
                    aria-live="polite"
                >
                    --:--:--
                </div>

                <p class="metric-label">Current Date and Time</p>
            </article>

            <article class="metric-card">
                <div class="metric-value">
                    <?php echo $professorsToday; ?>
                </div>
                <p class="metric-label">Professors on Campus Today</p>
            </article>

            <article class="metric-card">
                <div class="metric-value">
                    <?php echo $coursesToday; ?>
                </div>
                <p class="metric-label">Courses Held Today</p>
            </article>

            <article class="metric-card">
                <div class="metric-value">
                    <?php echo $studentsToday; ?>
                </div>
                <p class="metric-label">Students Participating Today</p>
            </article>
        </div>
    </section>
</main>

<?php include("includes/footer.php"); ?>

<script>
function updateDateAndTime() {
    const now = new Date();

    document.getElementById("current-date").textContent =
        now.toLocaleDateString("en-GB", {
            weekday: "long",
            day: "2-digit",
            month: "long",
            year: "numeric"
        });

    document.getElementById("current-time").textContent =
        now.toLocaleTimeString("en-GB", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            hour12: false
        });
}

updateDateAndTime();
setInterval(updateDateAndTime, 1000);
</script>

</body>
</html>
