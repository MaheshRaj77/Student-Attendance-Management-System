<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "attendance_system";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select database
$conn->select_db($dbname);

// Include database schema and functions
require_once 'db_schema.php';
require_once 'db_functions.php';

// Function to generate common header - only define if not already defined
if (!function_exists('getHeader')) {
    // In the getHeader function, update the sidebar links
    function getHeader($title, $active_page = '') {
        // Define sidebar with navigation links
        $sidebar = '<div class="list-group">
                        <a href="index.php" class="list-group-item list-group-item-action ' . ($active_page == 'dashboard' ? 'active' : '') . '">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a href="degrees.php" class="list-group-item list-group-item-action ' . ($active_page == 'degrees' ? 'active' : '') . '">
                            <i class="fas fa-graduation-cap me-2"></i> Manage Degrees
                        </a>
                        <a href="courses.php" class="list-group-item list-group-item-action ' . ($active_page == 'courses' ? 'active' : '') . '">
                            <i class="fas fa-book me-2"></i> Manage Courses
                        </a>
                        <a href="students.php" class="list-group-item list-group-item-action ' . ($active_page == 'students' ? 'active' : '') . '">
                            <i class="fas fa-user-graduate me-2"></i> Manage Students
                        </a>
                        <a href="attendance.php" class="list-group-item list-group-item-action ' . ($active_page == 'attendance' ? 'active' : '') . '">
                            <i class="fas fa-clipboard-check me-2"></i> Record Attendance
                        </a>
                        <a href="edit_attendance.php" class="list-group-item list-group-item-action ' . ($active_page == 'edit_attendance' ? 'active' : '') . '">
                            <i class="fas fa-edit me-2"></i> Edit Attendance
                        </a>
                        <a href="reports.php" class="list-group-item list-group-item-action ' . ($active_page == 'reports' ? 'active' : '') . '">
                            <i class="fas fa-chart-line me-2"></i> Attendance Reports
                        </a>
                        <a href="course_stats.php" class="list-group-item list-group-item-action ' . ($active_page == 'course_stats' ? 'active' : '') . '">
                            <i class="fas fa-chart-pie me-2"></i> Course Statistics
                        </a>
                    </div>';
        
        $header = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . $title . ' - Attendance System</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="assets/css/style.css">
        </head>
        <body>
            <div class="container">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h1 class="mb-0">Student Attendance System</h1>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        ' . $sidebar . '
                    </div>
                    <div class="col-md-9">';
        
        return $header;
    }
}

// Function to generate common footer - only define if not already defined
if (!function_exists('getFooter')) {
    function getFooter() {
        $footer = '
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                // Add fade-in animation to cards
                document.addEventListener("DOMContentLoaded", function() {
                    const cards = document.querySelectorAll(".card, .dashboard-card, .stat-card");
                    cards.forEach((card, index) => {
                        card.classList.add("fade-in");
                        card.style.animationDelay = `${index * 0.1}s`;
                    });
                    
                    // Add AJAX functionality to degree selection
                    const degreeSelect = document.getElementById("degree_id");
                    const courseSelect = document.getElementById("course_id");
                    
                    if (degreeSelect && courseSelect) {
                        degreeSelect.addEventListener("change", function() {
                            if (this.value) {
                                document.querySelector("button[name=\'select_degree\']").click();
                            }
                        });
                    }
                });
            </script>
        </body>
        </html>';
        
        return $footer;
    }
}

// Function to get attendance statistics for a student in a course
function getAttendanceStats($conn, $student_id, $course_id) {
    // Get total classes conducted for this course
    $total_classes_query = $conn->query("SELECT COUNT(DISTINCT date) as total FROM attendance WHERE course_id = $course_id");
    $total_classes = $total_classes_query->fetch_assoc()['total'];
    
    // Get classes attended by the student
    $attended_classes_query = $conn->query("SELECT COUNT(*) as attended FROM attendance 
                                           WHERE student_id = $student_id 
                                           AND course_id = $course_id 
                                           AND status = 'present'");
    $classes_attended = $attended_classes_query->fetch_assoc()['attended'];
    
    // Calculate percentage
    $percentage = ($total_classes > 0) ? round(($classes_attended / $total_classes) * 100, 2) : 0;
    
    return [
        'total_classes' => $total_classes,
        'classes_attended' => $classes_attended,
        'percentage' => $percentage
    ];
}