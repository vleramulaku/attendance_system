<?php
include("includes/db.php");

$search = trim($_GET["search"] ?? "");

$semesterName = "Summer Semester";
$semesterStart = new DateTimeImmutable("2026-02-01");
$semesterEnd = new DateTimeImmutable("2026-08-31");

$semesterStartSql = $semesterStart->format("Y-m-d");
$semesterEndSql = $semesterEnd->format("Y-m-d");
$searchLike = "%" . $search . "%";

$sql = "
    SELECT
        p.professor_name,
        p.course,
        p.semester_course_target,
        COALESCE(semester.courses_held, 0) AS courses_held_semester
    FROM (
        SELECT
            professor_name,
            course,
            MAX(semester_course_target) AS semester_course_target
        FROM professors
        GROUP BY professor_name, course
    ) p
    LEFT JOIN (
        SELECT
            professor_name,
            course,
            COUNT(*) AS courses_held
        FROM course_sessions
        WHERE session_date BETWEEN ? AND ?
        GROUP BY professor_name, course
    ) semester
        ON semester.professor_name = p.professor_name
       AND semester.course = p.course
    WHERE
        ? = ''
        OR p.professor_name LIKE ?
        OR p.course LIKE ?
    ORDER BY
        p.professor_name ASC,
        p.course ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database setup is incomplete. Import course_targets_update.sql first.");
}

$stmt->bind_param(
    "sssss",
    $semesterStartSql,
    $semesterEndSql,
    $search,
    $searchLike,
    $searchLike
);

$stmt->execute();
$result = $stmt->get_result();

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
    <title>Professors</title>
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
            <h2>Professors</h2>

            <div class="semester-box">
                <strong><?php echo e($semesterName); ?></strong>
                <span>
                    <?php echo e($semesterStart->format("d M")); ?>
                    –
                    <?php echo e($semesterEnd->format("d M Y")); ?>
                </span>
            </div>
        </div>

        <form class="search-form" method="GET" action="professors.php">
            <input
                type="search"
                name="search"
                value="<?php echo e($search); ?>"
                placeholder="Search by professor name or course"
                aria-label="Search professors by name or course"
            >

            <button class="search-button" type="submit">Search</button>

            <?php if ($search !== ""): ?>
                <a class="clear-button" href="professors.php">Clear</a>
            <?php endif; ?>
        </form>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Professor</th>
                            <th>Course</th>
                            <th>Courses Held This Semester</th>
                            <th>Total Courses Required This Semester</th>
                            <th>Semester Completed</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            $coursesHeldSemester = (int)$row["courses_held_semester"];
                            $semesterTarget = (int)$row["semester_course_target"];

                            $completion = $semesterTarget > 0
                                ? min(100, ($coursesHeldSemester / $semesterTarget) * 100)
                                : 0;
                            ?>
                            <tr>
                                <td>
                                    <span class="professor-name">
                                        <?php echo e($row["professor_name"]); ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="course-tag">
                                        <?php echo e($row["course"]); ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="semester-count">
                                        <?php echo $coursesHeldSemester; ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="target-count">
                                        <?php echo $semesterTarget; ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="completion-value">
                                        <?php echo number_format($completion, 1); ?>%
                                    </span>

                                    <progress
                                        class="progress-track"
                                        value="<?php echo number_format($completion, 1, '.', ''); ?>"
                                        max="100"
                                    ></progress>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-message">
                <?php if ($search !== ""): ?>
                    No professors matched your search.
                <?php else: ?>
                    No professors are registered in the database.
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
