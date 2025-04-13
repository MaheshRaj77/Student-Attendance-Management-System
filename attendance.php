<?php
include 'config.php';

$degrees = $conn->query("SELECT * FROM degrees ORDER BY name");
$selected_degree = $selected_course = '';
$students = [];
$error_message = '';

// Handle degree selection
if (isset($_POST['select_degree'])) {
    $selected_degree = $_POST['degree_id'];
    
    // Get courses for the selected degree
    $courses = $conn->query("SELECT * FROM courses WHERE degree_id = $selected_degree ORDER BY course_code");
}

// Handle course selection
if (isset($_POST['select_course'])) {
    $selected_degree = $_POST['degree_id'];
    $selected_course = $_POST['course_id'];
    
    // Get students for the selected degree
    $students_query = $conn->query("SELECT * FROM students WHERE degree_id = $selected_degree ORDER BY roll_no");
    
    // Get courses for the selected degree (for dropdown)
    $courses = $conn->query("SELECT * FROM courses WHERE degree_id = $selected_degree ORDER BY course_code");
    
    // Check if attendance already exists for today
    $today = date('Y-m-d');
    $check_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance 
                                     WHERE course_id = $selected_course 
                                     AND date = '$today'");
    $attendance_exists = $check_attendance->fetch_assoc()['count'] > 0;
    
    if ($attendance_exists) {
        $error_message = "Attendance for this course has already been recorded today.";
        $students = [];
    } else {
        // Fetch students
        while ($row = $students_query->fetch_assoc()) {
            $students[] = $row;
        }
    }
}

// Handle attendance submission
if (isset($_POST['save_attendance'])) {
    $selected_degree = $_POST['degree_id'];
    $selected_course = $_POST['course_id'];
    $student_ids = $_POST['student_id'];
    $statuses = $_POST['status'];
    $date = date('Y-m-d');
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert attendance records
        $sql = "INSERT INTO attendance (student_id, course_id, status, date) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        foreach ($student_ids as $index => $student_id) {
            $status = $statuses[$index];
            $stmt->bind_param("iiss", $student_id, $selected_course, $status, $date);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Redirect to avoid form resubmission
        header("Location: attendance.php?success=1");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Start output buffering
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-clipboard-check me-2"></i> Record Attendance</h5>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> Attendance has been recorded successfully!
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($selected_degree)): ?>
            <form method="post">
                <div class="mb-3">
                    <label for="degree_id" class="form-label">Select Degree</label>
                    <select class="form-select" id="degree_id" name="degree_id" required>
                        <option value="">Select Degree</option>
                        <?php while($degree = $degrees->fetch_assoc()): ?>
                            <option value="<?php echo $degree['id']; ?>"><?php echo $degree['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" name="select_degree" class="btn btn-primary">
                    <i class="fas fa-arrow-right me-2"></i> Next
                </button>
            </form>
        <?php elseif (empty($selected_course)): ?>
            <form method="post">
                <input type="hidden" name="degree_id" value="<?php echo $selected_degree; ?>">
                <div class="mb-3">
                    <label for="course_id" class="form-label">Select Course</label>
                    <select class="form-select" id="course_id" name="course_id" required>
                        <option value="">Select Course</option>
                        <?php while($course = $courses->fetch_assoc()): ?>
                            <option value="<?php echo $course['id']; ?>"><?php echo $course['course_code'] . ' - ' . $course['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" name="select_course" class="btn btn-primary">
                    <i class="fas fa-arrow-right me-2"></i> Next
                </button>
                <a href="attendance.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Back
                </a>
            </form>
        <?php elseif (!empty($students)): ?>
            <form method="post">
                <input type="hidden" name="degree_id" value="<?php echo $selected_degree; ?>">
                <input type="hidden" name="course_id" value="<?php echo $selected_course; ?>">
                
                <div class="mb-3">
                    <h6>Date: <?php echo date('Y-m-d'); ?></h6>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Roll Number</th>
                            <th>Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo $student['roll_no']; ?></td>
                                <td><?php echo $student['name']; ?></td>
                                <td>
                                    <input type="hidden" name="student_id[]" value="<?php echo $student['id']; ?>">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status[<?php echo $student['id']; ?>]" id="present_<?php echo $student['id']; ?>" value="present" checked>
                                        <label class="form-check-label" for="present_<?php echo $student['id']; ?>">Present</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="status[<?php echo $student['id']; ?>]" id="absent_<?php echo $student['id']; ?>" value="absent">
                                        <label class="form-check-label" for="absent_<?php echo $student['id']; ?>">Absent</label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button type="submit" name="save_attendance" class="btn btn-success">
                    <i class="fas fa-save me-2"></i> Save Attendance
                </button>
                <a href="attendance.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i> Cancel
                </a>
            </form>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> No students found for the selected degree or attendance already recorded for today.
            </div>
            <a href="attendance.php" class="btn btn-primary">
                <i class="fas fa-redo me-2"></i> Start Over
            </a>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
echo getHeader('Record Attendance', 'attendance');
echo $content;
echo getFooter();
?>