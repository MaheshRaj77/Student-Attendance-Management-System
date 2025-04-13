<?php
include 'config.php';

$degrees = $conn->query("SELECT * FROM degrees ORDER BY name");
$selected_degree = $selected_course = '';
$courses = null;
$stats = null;

// Handle degree selection
if (isset($_POST['select_degree'])) {
    $selected_degree = $_POST['degree_id'];
    
    // Get courses for the selected degree
    $courses = $conn->query("SELECT * FROM courses WHERE degree_id = $selected_degree ORDER BY course_code");
}

// Handle form submission for statistics
if (isset($_POST['view_stats'])) {
    $selected_degree = $_POST['degree_id'];
    $selected_course = $_POST['course_id'];
    
    // Get courses for the selected degree (for dropdown)
    $courses = $conn->query("SELECT * FROM courses WHERE degree_id = $selected_degree ORDER BY course_code");
    
    // Get course details
    $course_details = $conn->query("SELECT c.*, d.name as degree_name FROM courses c 
                                   JOIN degrees d ON c.degree_id = d.id 
                                   WHERE c.id = $selected_course")->fetch_assoc();
    
    // Get total classes conducted for this course
    $total_classes_query = $conn->query("SELECT COUNT(DISTINCT date) as total FROM attendance WHERE course_id = $selected_course");
    $total_classes = $total_classes_query->fetch_assoc()['total'];
    
    // Get students enrolled in this course's degree
    $students_query = $conn->query("SELECT * FROM students WHERE degree_id = {$course_details['degree_id']} ORDER BY roll_no");
    $students = [];
    
    while ($student = $students_query->fetch_assoc()) {
        // Get attendance stats for this student in this course
        $student_stats = getAttendanceStats($conn, $student['id'], $selected_course);
        $student['attendance'] = $student_stats;
        $students[] = $student;
    }
    
    // Calculate overall attendance percentage
    $overall_percentage = 0;
    $student_count = count($students);
    
    if ($student_count > 0) {
        $total_percentage = 0;
        foreach ($students as $student) {
            $total_percentage += $student['attendance']['percentage'];
        }
        $overall_percentage = round($total_percentage / $student_count, 2);
    }
    
    $stats = [
        'course' => $course_details,
        'total_classes' => $total_classes,
        'students' => $students,
        'overall_percentage' => $overall_percentage
    ];
}

// Start output buffering
ob_start();
?>

<style>
.stat-card {
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.stat-card .icon {
    font-size: 2rem;
    margin-bottom: 10px;
}

.stat-card .number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-card .label {
    font-size: 1rem;
}
</style>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-chart-pie me-2"></i> Course Attendance Statistics</h5>
    </div>
    <div class="card-body">
        <form method="post" class="mb-4">
            <div class="row">
                <div class="col-md-5">
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
                <div class="col-md-5">
                    <div class="mb-3">
                        <label for="course_id" class="form-label">Select Course</label>
                        <select class="form-select" id="course_id" name="course_id" required <?php echo empty($selected_degree) ? 'disabled' : ''; ?>>
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
                <div class="col-md-2 d-flex align-items-end">
                    <div class="mb-3">
                        <button type="submit" name="select_degree" class="btn btn-secondary me-2">
                            <i class="fas fa-sync-alt me-2"></i> Update Courses
                        </button>
                    </div>
                </div>
            </div>
            <button type="submit" name="view_stats" class="btn btn-primary" <?php echo empty($selected_degree) ? 'disabled' : ''; ?>>
                <i class="fas fa-search me-2"></i> View Statistics
            </button>
        </form>
        
        <?php if ($stats): ?>
            <div class="student-info">
                <h5>
                    <i class="fas fa-book me-2"></i>
                    <?php echo $stats['course']['course_code'] . ' - ' . $stats['course']['name']; ?>
                </h5>
                <p class="mb-0">
                    <strong>Degree:</strong> <?php echo $stats['course']['degree_name']; ?>
                    <br><strong>Total Classes Conducted:</strong> <?php echo $stats['total_classes']; ?>
                </p>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="stat-card bg-primary text-white">
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="number"><?php echo count($stats['students']); ?></div>
                        <div class="label">Total Students</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-success text-white">
                        <div class="icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="number"><?php echo $stats['total_classes']; ?></div>
                        <div class="label">Classes Conducted</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-info text-white">
                        <div class="icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="number"><?php echo $stats['overall_percentage']; ?>%</div>
                        <div class="label">Overall Attendance</div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h6>Overall Attendance Progress</h6>
                <?php
                $progress_class = 'bg-danger';
                if ($stats['overall_percentage'] >= 75) {
                    $progress_class = 'bg-success';
                } elseif ($stats['overall_percentage'] >= 60) {
                    $progress_class = 'bg-warning';
                }
                ?>
                <div class="progress">
                    <div class="progress-bar <?php echo $progress_class; ?>" role="progressbar" 
                         style="width: <?php echo $stats['overall_percentage']; ?>%" 
                         aria-valuenow="<?php echo $stats['overall_percentage']; ?>" 
                         aria-valuemin="0" aria-valuemax="100">
                        <?php echo $stats['overall_percentage']; ?>%
                    </div>
                </div>
            </div>
            
            <h5 class="mt-4">Student-wise Attendance</h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Roll Number</th>
                            <th>Name</th>
                            <th>Classes Attended</th>
                            <th>Attendance %</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['students'] as $student): ?>
                            <tr>
                                <td><?php echo $student['roll_no']; ?></td>
                                <td><?php echo $student['name']; ?></td>
                                <td><?php echo $student['attendance']['classes_attended'] . '/' . $student['attendance']['total_classes']; ?></td>
                                <td><?php echo $student['attendance']['percentage']; ?>%</td>
                                <td>
                                    <?php if ($student['attendance']['percentage'] >= 75): ?>
                                        <span class="badge bg-success">Good</span>
                                    <?php elseif ($student['attendance']['percentage'] >= 60): ?>
                                        <span class="badge bg-warning">Average</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Poor</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif (isset($_POST['view_stats'])): ?>
            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle me-2"></i> No attendance records found for the selected course.
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
                        
                        // Enable the course select
                        courseSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error fetching courses:', error);
                    });
            } else {
                // Clear course select if no degree is selected
                courseSelect.innerHTML = '<option value="">Select Course</option>';
                courseSelect.disabled = true;
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
echo getHeader('Course Statistics', 'course_stats');
echo $content;
echo getFooter();
?>