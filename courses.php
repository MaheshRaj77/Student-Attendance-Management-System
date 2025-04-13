<?php
include 'config.php';

// Handle form submission for adding
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    $course_code = $_POST['course_code'];
    $name = $_POST['name'];
    $degree_id = $_POST['degree_id'];
    
    $sql = "INSERT INTO courses (course_code, name, degree_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $course_code, $name, $degree_id);
    
    if ($stmt->execute()) {
        $success_message = "Course added successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Handle form submission for editing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_course'])) {
    $id = $_POST['id'];
    $course_code = $_POST['course_code'];
    $name = $_POST['name'];
    $degree_id = $_POST['degree_id'];
    
    $sql = "UPDATE courses SET course_code = ?, name = ?, degree_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $course_code, $name, $degree_id, $id);
    
    if ($stmt->execute()) {
        $success_message = "Course updated successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if course is being used in attendance
    $check_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE course_id = $id")->fetch_assoc();
    
    if ($check_attendance['count'] > 0) {
        $error_message = "Cannot delete: This course has attendance records.";
    } else {
        $sql = "DELETE FROM courses WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = "Course deleted successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Get all courses
$courses = $conn->query("SELECT c.*, d.name as degree_name FROM courses c 
                        JOIN degrees d ON c.degree_id = d.id 
                        ORDER BY c.course_code");

// Get all degrees for dropdown
$degrees = $conn->query("SELECT * FROM degrees ORDER BY name");

// Start output buffering
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-book me-2"></i> Manage Courses</h5>
    </div>
    <div class="card-body">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="course_code" class="form-label">Course Code</label>
                        <input type="text" class="form-control" id="course_code" name="course_code" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="name" class="form-label">Course Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="degree_id" class="form-label">Degree</label>
                        <select class="form-select" id="degree_id" name="degree_id" required>
                            <option value="">Select Degree</option>
                            <?php 
                            // Reset result pointer
                            $degrees->data_seek(0);
                            while($degree = $degrees->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $degree['id']; ?>"><?php echo $degree['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>
            <button type="submit" name="add_course" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i> Add Course
            </button>
        </form>
        
        <h5 class="mt-4">Courses List</h5>
        <table class="table">
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Degree</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($course = $courses->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $course['course_code']; ?></td>
                        <td><?php echo $course['name']; ?></td>
                        <td><?php echo $course['degree_name']; ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary edit-btn" 
                                    data-id="<?php echo $course['id']; ?>" 
                                    data-code="<?php echo $course['course_code']; ?>"
                                    data-name="<?php echo $course['name']; ?>"
                                    data-degree="<?php echo $course['degree_id']; ?>"
                                    data-bs-toggle="modal" data-bs-target="#editModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="courses.php?delete=<?php echo $course['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this course?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_course_code" class="form-label">Course Code</label>
                        <input type="text" class="form-control" id="edit_course_code" name="course_code" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Course Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_degree_id" class="form-label">Degree</label>
                        <select class="form-select" id="edit_degree_id" name="degree_id" required>
                            <option value="">Select Degree</option>
                            <?php 
                            // Reset result pointer
                            $degrees->data_seek(0);
                            while($degree = $degrees->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $degree['id']; ?>"><?php echo $degree['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_course" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit button clicks
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const code = this.getAttribute('data-code');
            const name = this.getAttribute('data-name');
            const degree = this.getAttribute('data-degree');
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_course_code').value = code;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_degree_id').value = degree;
        });
    });
});
</script>

<?php
$content = ob_get_clean();
echo getHeader('Manage Courses', 'courses');
echo $content;
echo getFooter();
?>