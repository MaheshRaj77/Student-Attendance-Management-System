<?php
include 'config.php';

// Handle form submission for adding
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $roll_no = $_POST['roll_no'];
    $name = $_POST['name'];
    $degree_id = $_POST['degree_id'];
    
    $sql = "INSERT INTO students (roll_no, name, degree_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $roll_no, $name, $degree_id);
    
    if ($stmt->execute()) {
        $success_message = "Student added successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Handle form submission for editing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_student'])) {
    $id = $_POST['id'];
    $roll_no = $_POST['roll_no'];
    $name = $_POST['name'];
    $degree_id = $_POST['degree_id'];
    
    $sql = "UPDATE students SET roll_no = ?, name = ?, degree_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $roll_no, $name, $degree_id, $id);
    
    if ($stmt->execute()) {
        $success_message = "Student updated successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if student has attendance records
    $check_attendance = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE student_id = $id")->fetch_assoc();
    
    if ($check_attendance['count'] > 0) {
        $error_message = "Cannot delete: This student has attendance records.";
    } else {
        $sql = "DELETE FROM students WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = "Student deleted successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Get all students
$students = $conn->query("SELECT s.*, d.name as degree_name FROM students s 
                         JOIN degrees d ON s.degree_id = d.id 
                         ORDER BY s.roll_no");

// Get all degrees for dropdown
$degrees = $conn->query("SELECT * FROM degrees ORDER BY name");

// Start output buffering
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-user-graduate me-2"></i> Manage Students</h5>
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
                        <label for="roll_no" class="form-label">Roll Number</label>
                        <input type="text" class="form-control" id="roll_no" name="roll_no" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
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
            <button type="submit" name="add_student" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i> Add Student
            </button>
        </form>
        
        <h5 class="mt-4">Students List</h5>
        <table class="table">
            <thead>
                <tr>
                    <th>Roll Number</th>
                    <th>Name</th>
                    <th>Degree</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($student = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $student['roll_no']; ?></td>
                        <td><?php echo $student['name']; ?></td>
                        <td><?php echo $student['degree_name']; ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary edit-btn" 
                                    data-id="<?php echo $student['id']; ?>" 
                                    data-roll="<?php echo $student['roll_no']; ?>"
                                    data-name="<?php echo $student['name']; ?>"
                                    data-degree="<?php echo $student['degree_id']; ?>"
                                    data-bs-toggle="modal" data-bs-target="#editModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="students.php?delete=<?php echo $student['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this student?')">
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
                <h5 class="modal-title" id="editModalLabel">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_roll_no" class="form-label">Roll Number</label>
                        <input type="text" class="form-control" id="edit_roll_no" name="roll_no" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
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
                    <button type="submit" name="edit_student" class="btn btn-primary">Save changes</button>
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
            const roll = this.getAttribute('data-roll');
            const name = this.getAttribute('data-name');
            const degree = this.getAttribute('data-degree');
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_roll_no').value = roll;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_degree_id').value = degree;
        });
    });
});
</script>

<?php
$content = ob_get_clean();
echo getHeader('Manage Students', 'students');
echo $content;
echo getFooter();
?>