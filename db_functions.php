<?php
// Function to get attendance statistics
// Only define getAttendanceStats if it doesn't already exist
if (!function_exists('getAttendanceStats')) {
    function getAttendanceStats($conn, $student_id, $course_id) {
        // Get total classes conducted for this course
        $total_classes_query = $conn->query("SELECT COUNT(DISTINCT date) as total FROM attendance WHERE course_id = $course_id");
        $total_classes = $total_classes_query->fetch_assoc()['total'];
        
        // Get classes attended by the student
        $attended_classes_query = $conn->query("SELECT COUNT(*) as attended FROM attendance 
                                               WHERE student_id = $student_id 
                                               AND course_id = $course_id 
                                               AND status = 'present'");
        $classes_attended = $attended_classes_query->fetch_assoc()['attended'];
        
        // Calculate percentage
        $percentage = ($total_classes > 0) ? round(($classes_attended / $total_classes) * 100, 2) : 0;
        
        return [
            'total_classes' => $total_classes,
            'classes_attended' => $classes_attended,
            'percentage' => $percentage
        ];
    }
}
?>