<?php
include 'config.php';

// Validate input
if (!isset($_GET['degree_id']) || !is_numeric($_GET['degree_id'])) {
    echo json_encode([]);
    exit;
}

$degree_id = intval($_GET['degree_id']);

// Prepare and execute query
$stmt = $conn->prepare("SELECT id, course_code, name FROM courses WHERE degree_id = ? ORDER BY course_code");
$stmt->bind_param("i", $degree_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all courses
$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($courses);
?>