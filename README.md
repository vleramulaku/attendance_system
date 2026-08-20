# Attendance Management System

Bachelor's thesis prototype that uses an ESP32 and HuskyLens face recognition to record professor-led course sessions and student attendance. The local web application displays daily dashboard statistics, professor and student semester progress, attendance records, and course/date searches.

## Project structure

- `api/` receives attendance and course-session requests from the ESP32.
- `css/` contains the shared website styling.
- `database/` contains the MySQL database setup files.
- `firmware/attendance_system/` contains the Arduino sketch.
- `includes/` contains the shared database connection, navigation, and footer.
- The PHP files in the root folder are the website pages.

## Requirements

- XAMPP with Apache, PHP, and MySQL
- Arduino IDE with ESP32 board support
- HuskyLens library
- RTClib library
- LiquidCrystal I2C library compatible with ESP32
- ESP32 DevKit V1, HuskyLens, DS3231 RTC, 20x4 I2C LCD, three buttons, two LEDs, and a buzzer

## Website setup

1. Copy the complete `attendance_system` folder to `C:\xampp\htdocs\`.
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`.
4. Import `database/attendance_system_schema.sql`.
5. If demonstration names and courses are needed, import `database/sample_reference_data.sql` after the schema.
6. Open `http://localhost/attendance_system/`.

The default database connection is in `includes/db.php` and uses the normal local XAMPP settings:

```text
Host: localhost
User: root
Password: empty
Database: attendance_system
```

## Arduino setup

1. Open `firmware/attendance_system/attendance_system.ino` in Arduino IDE.
2. Open `secrets.h` from the same sketch folder.
3. Replace the Wi-Fi placeholders with the real Wi-Fi name and password.
4. Confirm that `SERVER_IP` is the IPv4 address of the computer running XAMPP.
5. Select `ESP32 Dev Module`, select the correct COM port, and upload the sketch.

The real `secrets.h` file is ignored by Git. `secrets.example.h` is the safe template stored in the repository.

## Hardware pins

| Component | ESP32 pin |
|---|---:|
| HuskyLens T/TX | D16 / RX2 |
| HuskyLens R/RX | D17 / TX2 |
| LCD and RTC SDA | D21 |
| LCD and RTC SCL | D22 |
| Green LED | D25 |
| Red LED | D26 |
| Buzzer | D27 |
| UP button | D32 |
| DOWN button | D33 |
| OK button | D14 |

## GitHub upload

Open this folder in Visual Studio Code, choose **Source Control**, select **Initialize Repository**, commit the files, and choose **Publish Branch**. A private GitHub repository is recommended if real attendance records or personal information are added later.

## Notes

- PHP records the database date and time using the computer running XAMPP.
- The DS3231 provides the date and time shown by the ESP32. If its backup power was lost, the sketch sets it from the time at which the firmware was compiled.
- Each `START` request creates one course-session record, so the same course held twice on one day is counted twice.
- Each student is counted once in the dashboard's daily participation total, even if the student attends multiple courses that day.

