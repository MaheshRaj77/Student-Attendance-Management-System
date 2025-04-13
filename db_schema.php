<?php
// Create tables if not exists
$sql = "CREATE TABLE IF NOT EXISTS degrees (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS courses (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    degree_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (degree_id) REFERENCES degrees(id)
)";
$conn->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS students (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    roll_no VARCHAR(20) NOT NULL,
    name VARCHAR(100) NOT NULL,
    degree_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (degree_id) REFERENCES degrees(id)
)";
$conn->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS attendance (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    student_id INT(11) NOT NULL,
    course_id INT(11) NOT NULL,
    status ENUM('present', 'absent') NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
)";
$conn->query($sql);
?>