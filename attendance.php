<?php
include("includes/db.php");

$selectedDate = trim($_GET["date"] ?? "");
$selectedCourse = trim($_GET["course"] ?? "");
$hasSearch = $selectedDate !== "" && $selectedCourse !== "";

$courses = [];

$courseSql = "
    SELECT course
    FROM (
        SELECT
            CASE
                WHEN course = 'Artificial Intelligence' THEN 'Artificial Int.'
                ELSE course
            END AS course
        FROM professors

        UNION

        SELECT
            CASE
                WHEN course = 'Artificial Intelligence' THEN 'Artificial Int.'
                ELSE course
            END AS course
        FROM attendance
    ) combined_courses
    WHERE course <> ''
    ORDER BY course ASC
";

$courseResult = $conn->query($courseSql);

if ($courseResult) {
    while ($courseRow = $courseResult->fetch_assoc()) {
        $courses[] = $courseRow["course"];
    }
}

$result = null;
$recordCount = 0;
$resultStmt = null;

if ($hasSearch) {
    $resultSql = "
        SELECT
            student_name,
            course,
            date,
            entry_time,
            exit_time,
            status
        FROM attendance
        WHERE date = ?
          AND (
              CASE
                  WHEN course = 'Artificial Intelligence' THEN 'Artificial Int.'
                  ELSE course
              END
          ) = ?
        ORDER BY entry_time ASC
    ";

    $resultStmt = $conn->prepare($resultSql);

    if ($resultStmt) {
        $resultStmt->bind_param("ss", $selectedDate, $selectedCourse);
        $resultStmt->execute();
        $result = $resultStmt->get_result();
        $recordCount = $result ? $result->num_rows : 0;
    }
}

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
    <title>Search Attendance</title>
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
            <h2>Search Attendance</h2>
        </div>

        <form class="filter-form" method="GET" action="attendance.php">
            <div class="filter-grid">
                <div class="field">
                    <label for="date">Select Date</label>
                    <input
                        id="date"
                        type="date"
                        name="date"
                        value="<?php echo e($selectedDate); ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="course">Select Course</label>

                    <select id="course" name="course" required>
                        <option value="" <?php echo $selectedCourse === "" ? "selected" : ""; ?>>
                            Choose a course
                        </option>

                        <?php foreach ($courses as $course): ?>
                            <option
                                value="<?php echo e($course); ?>"
                                <?php echo $selectedCourse === $course ? "selected" : ""; ?>
                            >
                                <?php echo e($course); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button class="search-button" type="submit">Search</button>

                <?php if ($hasSearch): ?>
                    <a class="clear-button" href="attendance.php">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($hasSearch): ?>
            <div class="results">
                <div class="results-heading">
                    <h3>Attendance Results</h3>

                    <div class="result-count">
                        <?php echo $recordCount; ?>
                        <?php echo $recordCount === 1 ? "record" : "records"; ?>
                    </div>
                </div>

                <div class="result-context">
                    Course:
                    <strong><?php echo e($selectedCourse); ?></strong>
                    &nbsp;•&nbsp;
                    Date:
                    <strong><?php echo e($selectedDate); ?></strong>
                </div>

                <?php if ($result && $recordCount > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Date</th>
                                    <th>Entry Time</th>
                                    <th>Exit Time</th>
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
                                            <span class="student-name">
                                                <?php echo e($row["student_name"]); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="course-tag">
                                                <?php echo e(
                                                    $row["course"] === "Artificial Intelligence"
                                                        ? "Artificial Int."
                                                        : $row["course"]
                                                ); ?>
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
                        No attendance records were found for this course and date.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php
if ($resultStmt) {
    $resultStmt->close();
}

$conn->close();
?>

<?php include("includes/footer.php"); ?>

</body>
</html>
