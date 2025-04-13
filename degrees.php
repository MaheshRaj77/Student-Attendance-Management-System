<?php
include 'config.php';

// Handle form submission for adding
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_degree'])) {
    $name = $_POST['name'];
    
    $sql = "INSERT INTO degrees (name) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    
    if ($stmt->execute()) {
        $success_message = "Degree added successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Handle form submission for editing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_degree'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    
    $sql = "UPDATE degrees SET name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $name, $id);
    
    if ($stmt->execute()) {
        $success_message = "Degree updated successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    
    $stmt->close();
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if degree is being used in courses or students
    $check_courses = $conn->query("SELECT COUNT(*) as count FROM courses WHERE degree_id = $id")->fetch_assoc();
    $check_students = $conn->query("SELECT COUNT(*) as count FROM students WHERE degree_id = $id")->fetch_assoc();
    
    if ($check_courses['count'] > 0 || $check_students['count'] > 0) {
        $error_message = "Cannot delete: This degree is being used by courses or students.";
    } else {
        $sql = "DELETE FROM degrees WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success_message = "Degree deleted successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        
        $stmt->close();
    }
}

// Get all degrees
$degrees = $conn->query("SELECT * FROM degrees ORDER BY name");

// Start output buffering
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-graduation-cap me-2"></i> Manage Degrees</h5>
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
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Degree Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="submit" name="add_degree" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i> Add Degree
                    </button>
                </div>
            </div>
        </form>
        
        <h5 class="mt-4">Degrees List</h5>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Degree Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Reset result pointer
                $degrees->data_seek(0);
                while($degree = $degrees->fetch_assoc()): 
                ?>
                    <tr>
                        <td><?php echo $degree['id']; ?></td>
                        <td><?php echo $degree['name']; ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary edit-btn" 
                                    data-id="<?php echo $degree['id']; ?>" 
                                    data-name="<?php echo $degree['name']; ?>"
                                    data-bs-toggle="modal" data-bs-target="#editModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="degrees.php?delete=<?php echo $degree['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this degree?')">
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
                <h5 class="modal-title" id="editModalLabel">Edit Degree</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Degree Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_degree" class="btn btn-primary">Save changes</button>
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
            const name = this.getAttribute('data-name');
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
        });
    });
});
</script>

<?php
$content = ob_get_clean();
echo getHeader('Manage Degrees', 'degrees');
echo $content;
echo getFooter();
?>