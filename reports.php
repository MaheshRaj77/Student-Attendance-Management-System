<?php
include 'config.php';

$degrees = $conn->query("SELECT * FROM degrees ORDER BY name");
$selected_degree = $selected_course = $selected_date = '';
$attendance_data = [];

// Handle degree selection
if (isset($_POST['select_degree'])) {
    $selected_degree = $_POST['degree_id'];
    
    // Get courses for the selected degree
    $courses = $conn->query("SELECT * FROM courses WHERE degree_id = $selected_degree ORDER BY course_code");
}

// Handle form submission
if (isset($_POST['view_report'])) {
    $selected_degree = $_POST['degree_id'];
    $selected_course = $_POST['course_id'];
    $selected_date = $_POST['date'];
    
    // Get courses for the selected degree (for dropdown)
    $courses = $conn->query("SELECT * FROM courses WHERE degree_id = $selected_degree ORDER BY course_code");
    
    // Get attendance data
    $query = "SELECT a.id, a.status, a.date, s.roll_no, s.name as student_name, c.course_code, c.name as course_name 
              FROM attendance a 
              JOIN students s ON a.student_id = s.id 
              JOIN courses c ON a.course_id = c.id 
              WHERE c.id = $selected_course 
              AND s.degree_id = $selected_degree";
    
    if (!empty($selected_date)) {
        $query .= " AND a.date = '$selected_date'";
    }
    
    $query .= " ORDER BY a.date DESC, s.roll_no ASC";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $attendance_data[] = $row;
        }
    }
}

// Start output buffering
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-chart-line me-2"></i> Attendance Reports</h5>
    </div>
    <div class="card-body">
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
                            <?php if (isset($courses) && $courses->num_rows > 0): ?>
                                <?php 
                                // Reset the result pointer
                                $courses->data_seek(0);
                                while($course = $courses->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $course['id']; ?>" <?php echo ($selected_course == $course['id']) ? 'selected' : ''; ?>>
                                        <?php echo $course['course_code'] . ' - ' . $course['name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="date" class="form-label">Date (Optional)</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $selected_date; ?>">
                    </div>
                </div>
            </div>
            <button type="submit" name="select_degree" class="btn btn-secondary me-2">
                <i class="fas fa-sync-alt me-2"></i> Update Courses
            </button>
            <button type="submit" name="view_report" class="btn btn-primary">
                <i class="fas fa-search me-2"></i> View Report
            </button>
        </form>
        
        <div class="report-container">
            <?php if (!empty($attendance_data)): ?>
                <h5>Attendance Report</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Roll Number</th>
                            <th>Student Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_data as $data): ?>
                            <tr>
                                <td><?php echo $data['date']; ?></td>
                                <td><?php echo $data['roll_no']; ?></td>
                                <td><?php echo $data['student_name']; ?></td>
                                <td>
                                    <?php if ($data['status'] == 'present'): ?>
                                        <span class="badge bg-success">Present</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Absent</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif (isset($_POST['view_report'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No attendance records found for the selected criteria.
                </div>
            <?php endif; ?>
        </div>
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
echo getHeader('Attendance Reports', 'reports');
echo $content;
echo getFooter();
?>