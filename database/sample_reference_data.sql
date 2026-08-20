USE attendance_system;

INSERT INTO professors
    (professor_name, course, huskylens_id, semester_course_target)
VALUES
    ('Merita Mulaku', 'Accounting', 1, 14),
    ('Merita Mulaku', 'Economics', 1, 14),
    ('Gadaf Mulaku', 'Programming', 2, 14),
    ('Gadaf Mulaku', 'Databases', 2, 14)
ON DUPLICATE KEY UPDATE
    huskylens_id = VALUES(huskylens_id),
    semester_course_target = VALUES(semester_course_target);

INSERT INTO registered_students (student_name, huskylens_id)
VALUES
    ('Erijona Muji', 3),
    ('Laureta Doberdoli', 4),
    ('Lejla Nika', 5),
    ('Blerta Ibrahimi', 6),
    ('Sibora Kacuri', 7)
ON DUPLICATE KEY UPDATE
    huskylens_id = VALUES(huskylens_id);

