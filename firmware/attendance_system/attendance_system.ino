#include "HUSKYLENS.h"
#include "HardwareSerial.h"
#include <Wire.h>
#include "RTClib.h"
#include <LiquidCrystal_I2C.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include "secrets.h"

HUSKYLENS huskylens;
HardwareSerial mySerial(2);
RTC_DS3231 rtc;
LiquidCrystal_I2C lcd(0x27, 20, 4);

const int greenLED = 25;
const int redLED = 26;
const int buzzer = 27;
const int BTN_UP = 32;
const int BTN_DOWN = 33;
const int BTN_OK = 14;

const char *ssid = WIFI_SSID;
const char *password = WIFI_PASSWORD;

bool classActive = false;
String activeProfessor = "";
String activeCourse = "";

int state1 = 0;
int state2 = 0;
int state3 = 0;
int state4 = 0;
int state5 = 0;

int selected = 0;
const int totalCourses = 2;

String meritaCourses[2] = {"Accounting", "Economics"};
String gadafCourses[2] = {"Programming", "Databases"};
String *currentCourses = nullptr;

unsigned long lastDetectTime = 0;
const int cooldown = 3000;

void handleProfessor(String name, String courses[], DateTime now);
void handleStudent(String name, int &state, DateTime now);
void playEnterSound();
void playExitSound();
void playUnknownSound();
void printTime(DateTime time);
void sendAttendance(String student, String course, String status);
void sendSession(String professor, String course, String action);
String apiUrl(String endpoint);

void setup()
{
    Serial.begin(115200);
    WiFi.begin(ssid, password);

    Serial.print("Connecting to WiFi");
    unsigned long startAttempt = millis();

    while (
        WiFi.status() != WL_CONNECTED
        && millis() - startAttempt < 15000
    ) {
        delay(500);
        Serial.print(".");
    }

    Serial.println();

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("WiFi Connected!");
        Serial.print("ESP32 IP: ");
        Serial.println(WiFi.localIP());
    } else {
        Serial.println("WiFi Failed!");
    }

    mySerial.begin(9600, SERIAL_8N1, 16, 17);
    Wire.begin(21, 22);

    pinMode(greenLED, OUTPUT);
    pinMode(redLED, OUTPUT);
    pinMode(buzzer, OUTPUT);
    pinMode(BTN_UP, INPUT_PULLUP);
    pinMode(BTN_DOWN, INPUT_PULLUP);
    pinMode(BTN_OK, INPUT_PULLUP);

    lcd.init();
    lcd.backlight();
    lcd.print("System Starting...");
    delay(1500);
    lcd.clear();

    if (!rtc.begin()) {
        lcd.print("RTC ERROR!");
        while (true) {
            delay(100);
        }
    }

    if (rtc.lostPower()) {
        rtc.adjust(DateTime(F(__DATE__), F(__TIME__)));
    }

    if (!huskylens.begin(mySerial)) {
        lcd.print("HUSKY ERROR!");
        while (true) {
            delay(100);
        }
    }

    lcd.print("Waiting for");
    lcd.setCursor(0, 1);
    lcd.print("Professor...");
}

void loop()
{
    if (millis() - lastDetectTime < cooldown) {
        return;
    }

    if (!huskylens.request()) {
        return;
    }

    if (!huskylens.available()) {
        digitalWrite(greenLED, LOW);
        digitalWrite(redLED, LOW);
        return;
    }

    HUSKYLENSResult result = huskylens.read();
    DateTime now = rtc.now();

    if (result.ID == 1) {
        handleProfessor("Merita Mulaku", meritaCourses, now);
    } else if (result.ID == 2) {
        handleProfessor("Gadaf Mulaku", gadafCourses, now);
    } else if (result.ID == 3) {
        handleStudent("Erijona Muji", state1, now);
    } else if (result.ID == 4) {
        handleStudent("Laureta Doberdoli", state2, now);
    } else if (result.ID == 5) {
        handleStudent("Lejla Nika", state3, now);
    } else if (result.ID == 6) {
        handleStudent("Blerta Ibrahimi", state4, now);
    } else if (result.ID == 7) {
        handleStudent("Sibora Kacuri", state5, now);
    } else {
        lcd.clear();
        lcd.print("Unknown Person");
        lcd.setCursor(0, 1);
        lcd.print("Access Denied");
        digitalWrite(greenLED, LOW);
        digitalWrite(redLED, HIGH);
        playUnknownSound();
    }

    lastDetectTime = millis();
}

void handleProfessor(String name, String courses[], DateTime now)
{
    if (classActive) {
        if (activeProfessor == name) {
            lcd.clear();
            lcd.print("End Session?");
            lcd.setCursor(0, 1);
            lcd.print(activeCourse);
            lcd.setCursor(0, 2);
            lcd.print("OK = Yes");

            while (true) {
                if (digitalRead(BTN_OK) == LOW) {
                    sendSession(activeProfessor, activeCourse, "END");
                    classActive = false;
                    activeProfessor = "";
                    activeCourse = "";
                    state1 = 0;
                    state2 = 0;
                    state3 = 0;
                    state4 = 0;
                    state5 = 0;

                    Serial.println("----------------");
                    Serial.println("Session Ended");
                    digitalWrite(greenLED, LOW);
                    playExitSound();

                    lcd.clear();
                    lcd.print("Session Ended");
                    delay(1500);
                    lcd.clear();
                    lcd.print("Waiting for");
                    lcd.setCursor(0, 1);
                    lcd.print("Professor...");
                    return;
                }
            }
        }

        lcd.clear();
        lcd.print("Class Active");
        lcd.setCursor(0, 1);
        lcd.print(activeProfessor);
        delay(2000);
        return;
    }

    activeProfessor = name;
    currentCourses = courses;
    selected = 0;

    lcd.clear();
    lcd.print("Welcome");
    lcd.setCursor(0, 1);
    lcd.print(name);
    delay(1500);

    while (true) {
        lcd.clear();
        lcd.print("Select Course");

        for (int i = 0; i < totalCourses; i++) {
            lcd.setCursor(0, i + 1);
            lcd.print(i == selected ? "> " : "  ");
            lcd.print(currentCourses[i]);
        }

        if (digitalRead(BTN_UP) == LOW) {
            if (selected > 0) {
                selected--;
            }
            delay(180);
        }

        if (digitalRead(BTN_DOWN) == LOW) {
            if (selected < totalCourses - 1) {
                selected++;
            }
            delay(180);
        }

        if (digitalRead(BTN_OK) == LOW) {
            activeCourse = currentCourses[selected];
            classActive = true;
            sendSession(activeProfessor, activeCourse, "START");

            digitalWrite(greenLED, HIGH);
            digitalWrite(redLED, LOW);

            lcd.clear();
            lcd.print(activeCourse);
            lcd.setCursor(0, 1);
            lcd.print("Attendance");
            lcd.setCursor(0, 2);
            lcd.print("Started");

            DateTime startTime = rtc.now();
            Serial.println("----------------");
            Serial.print("Professor: ");
            Serial.println(activeProfessor);
            Serial.print("Course: ");
            Serial.println(activeCourse);
            Serial.print("Started: ");
            printTime(startTime);

            playEnterSound();
            delay(2000);
            return;
        }
    }
}

void handleStudent(String name, int &state, DateTime now)
{
    if (!classActive) {
        lcd.clear();
        lcd.print("No Active Class");
        playUnknownSound();
        delay(1500);
        return;
    }

    lcd.clear();
    lcd.print(name);
    lcd.setCursor(0, 1);
    lcd.print(activeCourse);

    if (state == 0) {
        state = 1;
        lcd.setCursor(0, 2);
        lcd.print("ENTER");
        playEnterSound();
        Serial.print(name);
        Serial.print(" ENTER ");
        sendAttendance(name, activeCourse, "ENTER");
    } else {
        state = 0;
        lcd.setCursor(0, 2);
        lcd.print("EXIT");
        playExitSound();
        Serial.print(name);
        Serial.print(" EXIT ");
        sendAttendance(name, activeCourse, "EXIT");
    }

    lcd.setCursor(0, 3);
    if (now.hour() < 10) {
        lcd.print("0");
    }
    lcd.print(now.hour());
    lcd.print(":");
    if (now.minute() < 10) {
        lcd.print("0");
    }
    lcd.print(now.minute());
    lcd.print(":");
    if (now.second() < 10) {
        lcd.print("0");
    }
    lcd.print(now.second());

    Serial.print(activeCourse);
    Serial.print(" ");
    printTime(now);
    delay(2000);
}

void playEnterSound()
{
    int notes[] = {523, 659, 784};
    for (int i = 0; i < 3; i++) {
        tone(buzzer, notes[i]);
        delay(120);
    }
    noTone(buzzer);
}

void playExitSound()
{
    int notes[] = {784, 659, 523};
    for (int i = 0; i < 3; i++) {
        tone(buzzer, notes[i]);
        delay(120);
    }
    noTone(buzzer);
}

void playUnknownSound()
{
    for (int i = 0; i < 3; i++) {
        tone(buzzer, 2000);
        delay(100);
        noTone(buzzer);
        delay(80);
    }
}

void printTime(DateTime time)
{
    if (time.hour() < 10) {
        Serial.print("0");
    }
    Serial.print(time.hour());
    Serial.print(":");
    if (time.minute() < 10) {
        Serial.print("0");
    }
    Serial.print(time.minute());
    Serial.print(":");
    if (time.second() < 10) {
        Serial.print("0");
    }
    Serial.println(time.second());
}

String apiUrl(String endpoint)
{
    return "http://" + String(SERVER_IP)
        + "/attendance_system/api/" + endpoint;
}

void sendSession(String professor, String course, String action)
{
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("WiFi not connected - session was not sent");
        return;
    }

    HTTPClient http;
    http.begin(apiUrl("addSession.php"));
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    String data =
        "professor_name=" + professor
        + "&course=" + course
        + "&action=" + action;

    int httpResponseCode = http.POST(data);
    Serial.print("Session HTTP Response: ");
    Serial.println(httpResponseCode);

    if (httpResponseCode > 0) {
        Serial.print("Session server says: ");
        Serial.println(http.getString());
    }

    http.end();
}

void sendAttendance(String student, String course, String status)
{
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("WiFi not connected - attendance was not sent");
        return;
    }

    HTTPClient http;
    http.begin(apiUrl("addAttendance.php"));
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");

    String data =
        "student_name=" + student
        + "&course=" + course
        + "&status=" + status;

    int httpResponseCode = http.POST(data);
    Serial.print("Attendance HTTP Response: ");
    Serial.println(httpResponseCode);

    if (httpResponseCode > 0) {
        Serial.print("Attendance server says: ");
        Serial.println(http.getString());
    }

    http.end();
}
