<?php
// Get subjects for specific user (or all if no user specified)
function getSubjects($userId = null) {
    $conn = new mysqli('localhost', 'root', '', 'cramtayo_db');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    if ($userId !== null) {
        // Get subjects for specific user
        $stmt = $conn->prepare("SELECT * FROM subjects WHERE user_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $subjects = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $subjects[] = $row;
            }
        }
        $stmt->close();
    } else {
        // Get all subjects (for backwards compatibility)
        $sql = "SELECT * FROM subjects ORDER BY created_at ASC";
        $result = mysqli_query($conn, $sql);
        
        $subjects = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $subjects[] = $row;
            }
        }
    }
    
    mysqli_close($conn);
    return $subjects;
}

// Add subject for specific user
function addSubject($subjectName, $displayName, $description, $imagePath = 'img/default-card.png', $userId = null) {
    $conn = new mysqli('localhost', 'root', '', 'cramtayo_db');
    if ($conn->connect_error) {
        return false;
    }
    
    // User must be logged in to create subjects
    if ($userId === null) {
        $conn->close();
        return false;
    }
    
    $tableName = 'study_cards_' . preg_replace('/[^a-zA-Z0-9]/', '', $subjectName);
    
    // Create database table
    $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tab_name VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        card_type ENUM('normal', 'long-card', 'long-card-4') DEFAULT 'normal',
        sort_order INT DEFAULT 0
    )";
    
    if ($conn->query($sql) === TRUE) {
        // Insert into subjects table with user_id
        $stmt = $conn->prepare("INSERT INTO subjects (user_id, subject_name, display_name, description, image_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $userId, $subjectName, $displayName, $description, $imagePath);
        
        if ($stmt->execute()) {
            $insertedId = $conn->insert_id;
            $stmt->close();
            $conn->close();
            return $insertedId;
        }
        $stmt->close();
    }
    
    $conn->close();
    return false;
}

// Delete subject (with ownership check)
function deleteSubject($subjectId, $userId = null) {
    $conn = new mysqli('localhost', 'root', '', 'cramtayo_db');
    if ($conn->connect_error) {
        return false;
    }
    
    // Get subject details and verify ownership
    if ($userId !== null) {
        $stmt = $conn->prepare("SELECT subject_name FROM subjects WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $subjectId, $userId);
    } else {
        $stmt = $conn->prepare("SELECT subject_name FROM subjects WHERE id = ?");
        $stmt->bind_param("i", $subjectId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $subjectName = $row['subject_name'];
        $tableName = 'study_cards_' . preg_replace('/[^a-zA-Z0-9]/', '', $subjectName);
        
        // Delete from subjects table
        $deleteStmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
        $deleteStmt->bind_param("i", $subjectId);
        
        if ($deleteStmt->execute()) {
            // Drop the study cards table
            $conn->query("DROP TABLE IF EXISTS `$tableName`");
            
            $deleteStmt->close();
            $stmt->close();
            $conn->close();
            return true;
        }
        $deleteStmt->close();
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

// Check if user owns a subject
function userOwnsSubject($subjectId, $userId) {
    $conn = new mysqli('localhost', 'root', '', 'cramtayo_db');
    if ($conn->connect_error) {
        return false;
    }
    
    $stmt = $conn->prepare("SELECT id FROM subjects WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $subjectId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $owns = $result->num_rows > 0;
    
    $stmt->close();
    $conn->close();
    
    return $owns;
}
?>