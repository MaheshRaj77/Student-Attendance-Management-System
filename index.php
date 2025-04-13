<?php
include 'config.php';

// Start output buffering
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-tachometer-alt me-2"></i> System Overview</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="dashboard-card bg-primary text-white">
                    <div class="icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as count FROM degrees");
                    $row = $result->fetch_assoc();
                    ?>
                    <div class="number"><?php echo $row['count']; ?></div>
                    <div class="label">Degrees</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card bg-success text-white">
                    <div class="icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as count FROM courses");
                    $row = $result->fetch_assoc();
                    ?>
                    <div class="number"><?php echo $row['count']; ?></div>
                    <div class="label">Courses</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card bg-info text-white">
                    <div class="icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as count FROM students");
                    $row = $result->fetch_assoc();
                    ?>
                    <div class="number"><?php echo $row['count']; ?></div>
                    <div class="label">Students</div>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="dashboard-card bg-warning text-white">
                    <div class="icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <?php
                    $result = $conn->query("SELECT COUNT(DISTINCT date, course_id) as count FROM attendance");
                    $row = $result->fetch_assoc();
                    ?>
                    <div class="number"><?php echo $row['count']; ?></div>
                    <div class="label">Classes Conducted</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="dashboard-card bg-danger text-white">
                    <div class="icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <?php
                    $result = $conn->query("SELECT COUNT(*) as count FROM attendance");
                    $row = $result->fetch_assoc();
                    ?>
                    <div class="number"><?php echo $row['count']; ?></div>
                    <div class="label">Attendance Records</div>
                </div>
            </div>
        </div>
        
        <div class="quick-links">
            <h5 class="mt-4 mb-3">Quick Links</h5>
            <div class="row">
                <div class="col-md-6">
                    <a href="attendance.php" class="btn btn-primary w-100">
                        <i class="fas fa-clipboard-check me-2"></i> Record Today's Attendance
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="student_stats.php" class="btn btn-success w-100">
                        <i class="fas fa-chart-bar me-2"></i> View Student Statistics
                    </a>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <a href="reports.php" class="btn btn-info w-100 text-white">
                        <i class="fas fa-chart-line me-2"></i> Generate Reports
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="students.php" class="btn btn-warning w-100">
                        <i class="fas fa-user-plus me-2"></i> Add New Student
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-info-circle me-2"></i> System Information</h5>
    </div>
    <div class="card-body">
        <p>Welcome to the Student Attendance System. This system helps you manage student attendance records efficiently.</p>
        <p>Use the menu on the left to navigate through different sections of the system.</p>
        <p>The <strong>Student Statistics</strong> feature allows you to view detailed attendance statistics for each student, including:</p>
        <ul>
            <li>Total number of classes conducted</li>
            <li>Number of classes attended</li>
            <li>Attendance percentage</li>
        </ul>
    </div>
</div>

<?php
$content = ob_get_clean();
echo getHeader('Dashboard', 'dashboard');
echo $content;
echo getFooter();
?>