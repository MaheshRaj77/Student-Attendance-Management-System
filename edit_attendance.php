<?php
include 'config.php';

$degrees = $conn->query("SELECT * FROM degrees ORDER BY name");
$selected_degree = $selected_course = $selected_date = '';
$attendance_data = [];
$success_message = $error_message = '';

// Handle degree selection
if (isset($_POST['select_degree'])) {
    $selected_degree = $_POST['degree_id'];
    
    // Get courses for the selected degree
    $courses = $conn->query("SELECT * FROM courses WHERE degree_id = $selected_degree ORDER BY course_code");
    
    // Debug: Check if courses were found
    if (!$courses || $courses->num_rows == 0) {
        $error_message = "No courses found for the selected degree. Please add courses first.";
    } else {
        // Debug: Log the number of courses found
        $success_message = "Found " . $courses->num_rows . " courses for the selected degree.";
    }
}

// Handle course and date selection
if (isset($_POST['select_course_date'])) {
    $selected_degree = $_POST['degree_id'];
    $selected_course = $_POST['course_id'];
    $selected_date = $_POST['date'];
    
    // Get courses for the selected degree (for dropdown)
    $courses = $conn->query("SELECT * FROM courses WHERE degree_id = $selected_degree ORDER BY course_code");
    
    // Get attendance data
    if (!empty($selected_date) && !empty($selected_course)) {
        $query = "SELECT a.id, a.student_id, a.status, s.roll_no, s.name as student_name 
                  FROM attendance a 
                  JOIN students s ON a.student_id = s.id 
                  WHERE a.course_id = $selected_course 
                  AND a.date = '$selected_date' 
                  ORDER BY s.roll_no";
        
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $attendance_data[] = $row;
            }
        }
    }
}

// Handle attendance update
if (isset($_POST['update_attendance'])) {
    $attendance_ids = $_POST['attendance_id'];
    $statuses = $_POST['status'];
    $selected_degree = $_POST['degree_id'];
    $selected_course = $_POST['course_id'];
    $selected_date = $_POST['date'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update attendance records
        $sql = "UPDATE attendance SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        foreach ($attendance_ids as $index => $id) {
            // Fix: Make sure we have a status for this index
            if (isset($statuses[$index])) {
                $status = $statuses[$index];
                $stmt->bind_param("si", $status, $id);
                $stmt->execute();
            }
        }
        
        // Commit transaction
        $conn->commit();
        $success_message = "Attendance updated successfully!";
        
        // Get courses for the selected degree (for dropdown)
        $courses = $conn->query("SELECT * FROM courses WHERE degree_id = $selected_degree ORDER BY course_code");
        
        // Refresh attendance data
        $query = "SELECT a.id, a.student_id, a.status, s.roll_no, s.name as student_name 
                  FROM attendance a 
                  JOIN students s ON a.student_id = s.id 
                  WHERE a.course_id = $selected_course 
                  AND a.date = '$selected_date' 
                  ORDER BY s.roll_no";
        
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $attendance_data = [];
            while ($row = $result->fetch_assoc()) {
                $attendance_data[] = $row;
            }
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Handle attendance deletion
if (isset($_POST['delete_attendance'])) {
    $selected_degree = $_POST['degree_id'];
    $selected_course = $_POST['course_id'];
    $selected_date = $_POST['date'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete attendance records
        $sql = "DELETE FROM attendance WHERE course_id = ? AND date = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $selected_course, $selected_date);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        $success_message = "Attendance records deleted successfully!";
        
        // Reset selections
        $selected_course = '';
        $selected_date = '';
        $attendance_data = [];
        
        // Get courses for the selected degree (for dropdown)
        $courses = $conn->query("SELECT * FROM courses WHERE degree_id = $selected_degree ORDER BY course_code");
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
        <h5><i class="fas fa-edit me-2"></i> Edit Attendance</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="degree_id" class="form-label">Select Degree</label>
                        <select class="form-select" id="degree_id" name="degree_id" required>
                            <option value="">Select Degree</option>
                            <?php while($degree = $degrees->fetch_assoc()): ?>
                                <option value="<?php echo $degree['id']; ?>" <?php echo ($selected_degree == $degree['id']) ? 'selected' : ''; ?>>
                                    <?php echo $degree['name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="course_id" class="form-label">Select Course</label>
                        <select class="form-select" id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php 
                            if (isset($courses) && $courses->num_rows > 0): 
                                // Reset the result pointer
                                $courses->data_seek(0);
                                while($course = $courses->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $course['id']; ?>" <?php echo ($selected_course == $course['id']) ? 'selected' : ''; ?>>
                                    <?php echo $course['course_code'] . ' - ' . $course['name']; ?>
                                </option>
                            <?php 
                                endwhile; 
                            endif; 
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $selected_date; ?>" required>
                    </div>
                </div>
            </div>
            <button type="submit" name="select_degree" class="btn btn-secondary me-2">
                <i class="fas fa-sync-alt me-2"></i> Update Courses
            </button>
            <button type="submit" name="select_course_date" class="btn btn-primary">
                <i class="fas fa-search me-2"></i> View Attendance
            </button>
        </form>
        
        <?php if (!empty($attendance_data)): ?>
            <form method="post">
                <input type="hidden" name="degree_id" value="<?php echo $selected_degree; ?>">
                <input type="hidden" name="course_id" value="<?php echo $selected_course; ?>">
                <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Attendance for <?php echo $selected_date; ?></h5>
                    <button type="submit" name="delete_attendance" class="btn btn-danger" 
                            onclick="return confirm('Are you sure you want to delete all attendance records for this date?')">
                        <i class="fas fa-trash me-2"></i> Delete All Records
                    </button>
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
                        <?php foreach ($attendance_data as $index => $data): ?>
                            <tr>
                                <td><?php echo $data['roll_no']; ?></td>
                                <td><?php echo $data['student_name']; ?></td>
                                <td>
                                    <input type="hidden" name="attendance_id[]" value="<?php echo $data['id']; ?>">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" 
                                               name="status[<?php echo $index; ?>]" 
                                               id="present_<?php echo $data['id']; ?>" 
                                               value="present" 
                                               <?php echo ($data['status'] == 'present') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="present_<?php echo $data['id']; ?>">Present</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" 
                                               name="status[<?php echo $index; ?>]" 
                                               id="absent_<?php echo $data['id']; ?>" 
                                               value="absent" 
                                               <?php echo ($data['status'] == 'absent') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="absent_<?php echo $data['id']; ?>">Absent</label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button type="submit" name="update_attendance" class="btn btn-success">
                    <i class="fas fa-save me-2"></i> Update Attendance
                </button>
            </form>
        <?php elseif (isset($_POST['select_course_date'])): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> No attendance records found for the selected date.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add AJAX functionality to degree selection
    const degreeSelect = document.getElementById('degree_id');
    const courseSelect = document.getElementById('course_id');
    
    if (degreeSelect) {
        degreeSelect.addEventListener('change', function() {
            if (this.value) {
                console.log('Degree selected: ' + this.value); // Debug log
                
                // Fetch courses via AJAX instead of submitting the form
                fetch('get_courses.php?degree_id=' + this.value)
                    .then(response => response.json())
                    .then(data => {
                        // Clear current options
                        courseSelect.innerHTML = '<option value="">Select Course</option>';
                        
                        // Add new options
                        data.forEach(course => {
                            const option = document.createElement('option');
                            option.value = course.id;
                            option.textContent = course.course_code + ' - ' + course.name;
                            courseSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching courses:', error);
                    });
            } else {
                // Clear course select if no degree is selected
                courseSelect.innerHTML = '<option value="">Select Course</option>';
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
echo getHeader('Edit Attendance', 'edit_attendance');
echo $content;
echo getFooter();
?>