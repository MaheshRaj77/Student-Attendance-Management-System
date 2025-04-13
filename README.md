# Student Attendance Management System

## Overview
The Student Attendance Management System is a comprehensive web-based application designed to track and manage student attendance across various courses and degree programs. This system provides an efficient way for educational institutions to record, monitor, and analyze attendance patterns.

## Features
- **Degree Management**: Add, edit, and delete degree programs  
- **Course Management**: Manage courses associated with specific degree programs  
- **Student Management**: Maintain student records with roll numbers and degree affiliations  
- **Attendance Recording**: Record daily attendance for students in specific courses  
- **Attendance Editing**: Modify previously recorded attendance data  
- **Statistical Analysis**:  
  - Course-wise attendance statistics  
  - Student-wise attendance reports  
  - Overall attendance percentages  
- **Reporting**: Generate detailed attendance reports with filtering options  

## Technical Details
- **Backend**: PHP 8.2+
- **Database**: MariaDB 10.4+
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **Dependencies**:
  - Font Awesome 6.0
  - Bootstrap 5.3
  - jQuery (for AJAX functionality)

## Installation
1. Clone the repository to your web server directory:
    ```bash
    (https://github.com/MaheshRaj77/Student-Attendance-Management-System.git)
    ```

2. Import the database schema:
    ```
    Just Clone and run Databse will be created atuomatically.
    ```

3. Configure the database connection in `config.php`:
    ```php
    $servername = "localhost";
    $username = "your_username";
    $password = "your_password";
    $dbname = "attendance_system";
    ```

4. Access the system through your web browser:
    ```
    http://localhost/attendance-system/
    ```

## System Structure
- `config.php` : Database connection and global functions  
- `db_schema.php` : Database schema definition  
- `db_functions.php` : Common database operations  
- `index.php` : Dashboard with system overview  
- `degrees.php` : Degree management interface  
- `courses.php` : Course management interface  
- `students.php` : Student management interface  
- `attendance.php` : Daily attendance recording  
- `edit_attendance.php` : Edit previously recorded attendance  
- `reports.php` : Generate attendance reports  
- `course_stats.php` : Course-wise attendance statistics  
- `student_stats.php` : Student-wise attendance statistics  

## Usage Guide

### Recording Attendance
1. Navigate to **"Record Attendance"** in the sidebar  
2. Select the degree program  
3. Select the course  
4. Mark students as present or absent  
5. Submit the attendance record  

### Viewing Statistics

#### Course Statistics:
- Navigate to **"Course Statistics"**  
- Select the degree and course  
- View detailed attendance statistics  

#### Student Statistics:
- Navigate to **"Student Statistics"** (accessible from reports)  
- Select a student and optionally a course  
- View the student's attendance record  

### Generating Reports
1. Navigate to **"Attendance Reports"**  
2. Select filtering criteria (degree, course, date)  
3. View the attendance report  
4. Export or print as needed  

## Security Considerations
- Uses **prepared statements** to prevent SQL injection  
- **Input validation** for all form submissions  
- **Session management** for user authentication (to be implemented)  

## Future Enhancements
- User authentication and role-based access control  
- Email notifications for low attendance  
- Mobile-responsive design improvements  
- API integration for external systems  
- Automated attendance marking using QR codes or RFID  

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
