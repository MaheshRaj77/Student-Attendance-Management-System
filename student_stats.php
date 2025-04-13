<?php
include 'config.php';

$selected_student = $selected_course = '';
$stats = null;

// Get all students for dropdown
$students = $conn->query("SELECT s.*, d.name as degree_name FROM students s 
                         JOIN degrees d ON s.degree_id = d.id 
                         ORDER BY s.roll_no");

// Handle form submission
if (isset($_POST['view_stats'])) {
    $selected_student = $_POST['student_id'];
    $selected_course = !empty($_POST['course_id']) ? $_POST['course_id'] : '';
    
    // Get courses for the selected student's degree
    $student_query = $conn->query("SELECT degree_id FROM students WHERE id = $selected_student");
    if ($student_row = $student_query->fetch_assoc()) {
        $degree_id = $student_row['degree_id'];
        $courses = $conn->query("SELECT * FROM courses WHERE degree_id = $degree_id ORDER BY course_code");
    }
    
    // Get attendance statistics - make sure we're passing a valid value for course_id
    if (!empty($selected_course)) {
        // Get stats for specific course
        $stats = getAttendanceStats($conn, $selected_student, $selected_course);
    } else {
        // Get overall stats across all courses
        // We need to implement a custom query for this case
        $stats = getOverallAttendanceStats($conn, $selected_student);
    }
    
    // Get student details
    $student_details = $conn->query("SELECT s.*, d.name as degree_name FROM students s 
                                    JOIN degrees d ON s.degree_id = d.id 
                                    WHERE s.id = $selected_student")->fetch_assoc();
    
    // Get course details if selected
    $course_details = null;
    if (!empty($selected_course)) {
        $course_details = $conn->query("SELECT * FROM courses WHERE id = $selected_course")->fetch_assoc();
    }
}

// Function to get overall attendance stats across all courses
function getOverallAttendanceStats($conn, $student_id) {
    // Get total classes across all courses for this student's degree
    $degree_query = $conn->query("SELECT degree_id FROM students WHERE id = $student_id");
    $degree_id = $degree_query->fetch_assoc()['degree_id'];
    
    // Get all course IDs for this degree
    $course_ids = [];
    $courses_query = $conn->query("SELECT id FROM courses WHERE degree_id = $degree_id");
    while ($course = $courses_query->fetch_assoc()) {
        $course_ids[] = $course['id'];
    }
    
    if (empty($course_ids)) {
        return [
            'total_classes' => 0,
            'classes_attended' => 0,
            'percentage' => 0
        ];
    }
    
    $course_ids_str = implode(',', $course_ids);
    
    // Get total classes
    $total_classes_query = $conn->query("SELECT COUNT(DISTINCT CONCAT(course_id, '_', date)) as total 
                                        FROM attendance 
                                        WHERE course_id IN ($course_ids_str)");
    $total_classes = $total_classes_query->fetch_assoc()['total'];
    
    // Get classes attended
    $attended_query = $conn->query("SELECT COUNT(*) as attended 
                                   FROM attendance 
                                   WHERE student_id = $student_id 
                                   AND course_id IN ($course_ids_str) 
                                   AND status = 'present'");
    $classes_attended = $attended_query->fetch_assoc()['attended'];
    
    // Calculate percentage
    $percentage = ($total_classes > 0) ? round(($classes_attended / $total_classes) * 100, 2) : 0;
    
    return [
        'total_classes' => $total_classes,
        'classes_attended' => $classes_attended,
        'percentage' => $percentage
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
        <h5><i class="fas fa-chart-bar me-2"></i> Student Attendance Statistics</h5>
    </div>
    <div class="card-body">
        <form method="post" class="mb-4">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Select Student</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php while($student = $students->fetch_assoc()): ?>
                                <option value="<?php echo $student['id']; ?>" <?php echo ($selected_student == $student['id']) ? 'selected' : ''; ?>>
                                    <?php echo $student['roll_no'] . ' - ' . $student['name'] . ' (' . $student['degree_name'] . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="course_id" class="form-label">Select Course (Optional)</label>
                        <select class="form-select" id="course_id" name="course_id">
                            <option value="">All Courses</option>
                            <?php if (isset($courses) && $courses->num_rows > 0): ?>
                                <?php while($course = $courses->fetch_assoc()): ?>
                                    <option value="<?php echo $course['id']; ?>" <?php echo ($selected_course == $course['id']) ? 'selected' : ''; ?>>
                                        <?php echo $course['course_code'] . ' - ' . $course['name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
            <button type="submit" name="view_stats" class="btn btn-primary">
                <i class="fas fa-search me-2"></i> View Statistics
            </button>
        </form>
        
        <?php if ($stats): ?>
            <div class="student-info">
                <h5>
                    <i class="fas fa-user me-2"></i>
                    <?php echo $student_details['name']; ?> (<?php echo $student_details['roll_no']; ?>)
                </h5>
                <p class="mb-0">
                    <strong>Degree:</strong> <?php echo $student_details['degree_name']; ?>
                    <?php if ($course_details): ?>
                        <br><strong>Course:</strong> <?php echo $course_details['course_code'] . ' - ' . $course_details['name']; ?>
                    <?php else: ?>
                        <br><strong>Course:</strong> All Courses
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card bg-primary text-white">
                        <div class="icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="number"><?php echo $stats['total_classes']; ?></div>
                        <div class="label">Total Classes</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-success text-white">
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="number"><?php echo $stats['classes_attended']; ?></div>
                        <div class="label">Classes Attended</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card bg-info text-white">
                        <div class="icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="number"><?php echo $stats['percentage']; ?>%</div>
                        <div class="label">Attendance Percentage</div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h6>Attendance Progress</h6>
                <?php
                $progress_class = 'bg-danger';
                if ($stats['percentage'] >= 75) {
                    $progress_class = 'bg-success';
                } elseif ($stats['percentage'] >= 60) {
                    $progress_class = 'bg-warning';
                }
                ?>
                <div class="progress">
                    <div class="progress-bar <?php echo $progress_class; ?>" role="progressbar" 
                         style="width: <?php echo $stats['percentage']; ?>%" 
                         aria-valuenow="<?php echo $stats['percentage']; ?>" 
                         aria-valuemin="0" aria-valuemax="100">
                        <?php echo $stats['percentage']; ?>%
                    </div>
                </div>
                
                <div class="mt-3">
                    <?php if ($stats['percentage'] < 75): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Attendance is below 75%. Improvement needed.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-thumbs-up me-2"></i>
                            Good attendance record! Keep it up.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
echo getHeader('Student Statistics', 'stats');
echo $content;
echo getFooter();
?>