<?php
include("includes/db.php");

$search = trim($_GET["search"] ?? "");
$searchLike = "%" . $search . "%";

$sql = "
    SELECT
        id,
        student_name,
        course,
        date,
        entry_time,
        exit_time,
        status
    FROM attendance
    WHERE
        ? = ''
        OR student_name LIKE ?
        OR course LIKE ?
        OR CAST(date AS CHAR) LIKE ?
        OR status LIKE ?
    ORDER BY date DESC, entry_time DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Unable to load attendance records.");
}

$stmt->bind_param(
    "sssss",
    $search,
    $searchLike,
    $searchLike,
    $searchLike,
    $searchLike
);

$stmt->execute();
$result = $stmt->get_result();
$recordCount = $result ? $result->num_rows : 0;

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<header>
    <h1>Attendance Management System</h1>
    <?php include("includes/navbar.php"); ?>
</header>

<main class="container">
    <section class="card">
        <div class="page-heading">
            <h2>All Attendance Records</h2>

            <div class="record-count">
                <?php echo $recordCount; ?>
                <?php echo $recordCount === 1 ? "record" : "records"; ?>
            </div>
        </div>

        <form class="search-form" method="GET" action="view_attendance.php">
            <input
                type="search"
                name="search"
                value="<?php echo e($search); ?>"
                placeholder="Search by student, course, date, or status"
                aria-label="Search attendance records"
            >

            <button class="search-button" type="submit">Search</button>

            <?php if ($search !== ""): ?>
                <a class="clear-button" href="view_attendance.php">Clear</a>
            <?php endif; ?>
        </form>

        <?php if ($result && $recordCount > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Date</th>
                            <th>Entry</th>
                            <th>Exit</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            $status = strtoupper((string)$row["status"]);
                            $statusClass = $status === "ENTER"
                                ? "status-enter"
                                : "status-exit";
                            ?>

                            <tr>
                                <td>
                                    <span class="record-id">
                                        #<?php echo (int)$row["id"]; ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="student-name">
                                        <?php echo e($row["student_name"]); ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="course-tag">
                                        <?php echo e($row["course"]); ?>
                                    </span>
                                </td>

                                <td><?php echo e($row["date"]); ?></td>
                                <td><?php echo e($row["entry_time"]); ?></td>

                                <td>
                                    <?php echo $row["exit_time"] !== null
                                        ? e($row["exit_time"])
                                        : "—"; ?>
                                </td>

                                <td>
                                    <span class="status <?php echo e($statusClass); ?>">
                                        <?php echo e($status); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-message">
                <?php if ($search !== ""): ?>
                    No attendance records matched your search.
                <?php else: ?>
                    No attendance records found.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php
$stmt->close();
$conn->close();
?>

<?php include("includes/footer.php"); ?>

</body>
</html>
