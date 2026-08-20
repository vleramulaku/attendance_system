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
        students.student_name,
        courses.course,
        courses.semester_course_target,
        COALESCE(attended.courses_attended, 0) AS courses_attended_semester
    FROM registered_students students
    CROSS JOIN (
        SELECT
            course,
            MAX(semester_course_target) AS semester_course_target
        FROM professors
        GROUP BY course
    ) courses
    LEFT JOIN (
        SELECT
            student_name,
            CASE
                WHEN course = 'Artificial Intelligence' THEN 'Artificial Int.'
                ELSE course
            END AS course,
            COUNT(*) AS courses_attended
        FROM attendance
        WHERE date BETWEEN ? AND ?
        GROUP BY
            student_name,
            CASE
                WHEN course = 'Artificial Intelligence' THEN 'Artificial Int.'
                ELSE course
            END
    ) attended
        ON attended.student_name = students.student_name
       AND attended.course = courses.course
    WHERE
        ? = ''
        OR students.student_name LIKE ?
        OR courses.course LIKE ?
    ORDER BY
        students.student_name ASC,
        courses.course ASC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database setup is incomplete. Import students_progress_update.sql first.");
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
    <title>Students</title>
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
            <h2>Students</h2>

            <div class="semester-box">
                <strong><?php echo e($semesterName); ?></strong>
                <span>
                    <?php echo e($semesterStart->format("d M")); ?>
                    –
                    <?php echo e($semesterEnd->format("d M Y")); ?>
                </span>
            </div>
        </div>

        <form class="search-form" method="GET" action="students.php">
            <input
                type="search"
                name="search"
                value="<?php echo e($search); ?>"
                placeholder="Search by student name or course"
                aria-label="Search students by name or course"
            >

            <button class="search-button" type="submit">Search</button>

            <?php if ($search !== ""): ?>
                <a class="clear-button" href="students.php">Clear</a>
            <?php endif; ?>
        </form>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Course</th>
                            <th>Courses Attended This Semester</th>
                            <th>Total Courses Required This Semester</th>
                            <th>Semester Attendance</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            $coursesAttended = (int)$row["courses_attended_semester"];
                            $semesterTarget = (int)$row["semester_course_target"];

                            $completion = $semesterTarget > 0
                                ? min(100, ($coursesAttended / $semesterTarget) * 100)
                                : 0;
                            ?>

                            <tr>
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

                                <td>
                                    <span class="attended-count">
                                        <?php echo $coursesAttended; ?>
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
                    No students matched your search.
                <?php else: ?>
                    No students or courses are registered in the database.
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
