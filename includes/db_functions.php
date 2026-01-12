<?php
// Start session at the beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cramtayo_db');

/**
 * Get database connection using PDO (more secure and modern)
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

// =============================================
// SESSION & USER FUNCTIONS
// =============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

function logoutUser() {
    session_unset();
    session_destroy();
}

// =============================================
// USER ACCOUNT FUNCTIONS
// =============================================

function registerAccount($username, $email, $password) {
    try {
        $pdo = getDBConnection();
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            return false; // Email already exists
        }
        
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, `password`) VALUES (?, ?, ?)");
        return $stmt->execute([$username, $email, $password]);
        
    } catch (PDOException $e) {
        error_log("Register error: " . $e->getMessage());
        return false;
    }
}

function loginAccount($email, $password) {
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("SELECT user_id, username, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $email;
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            return true;
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

// =============================================
// SUBJECT FUNCTIONS
// =============================================

function getSubjectsDb($userId = null) {
    try {
        $pdo = getDBConnection();
        
        if ($userId !== null) {
            $stmt = $pdo->prepare("SELECT * FROM subjects WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->query("SELECT * FROM subjects ORDER BY created_at DESC");
        }
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Get subjects error: " . $e->getMessage());
        return [];
    }
}

function getSubjectById($subjectId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
        $stmt->execute([$subjectId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get subject by ID error: " . $e->getMessage());
        return null;
    }
}

function addSubjectDb($subjectName, $displayName, $description, $imagePath, $userId) {
    try {
        if ($userId === null) {
            return false;
        }
        
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare(
            "INSERT INTO subjects (user_id, subject_name, display_name, description, image_path) 
             VALUES (?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([$userId, $subjectName, $displayName, $description, $imagePath]);
        
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("Add subject error: " . $e->getMessage());
        return false;
    }
}

function deleteSubjectDb($subjectId, $userId = null) {
    try {
        $pdo = getDBConnection();
        
        // Verify ownership if userId provided
        if ($userId !== null) {
            $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ? AND user_id = ?");
            return $stmt->execute([$subjectId, $userId]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
            return $stmt->execute([$subjectId]);
        }
        
    } catch (PDOException $e) {
        error_log("Delete subject error: " . $e->getMessage());
        return false;
    }
}

function userOwnsSubjectDb($subjectId, $userId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND user_id = ?");
        $stmt->execute([$subjectId, $userId]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log("Check ownership error: " . $e->getMessage());
        return false;
    }
}

// =============================================
// SECTION FUNCTIONS
// =============================================

function getSections($subjectId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM sections WHERE subject_id = ? ORDER BY sort_order, section_name");
        $stmt->execute([$subjectId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get sections error: " . $e->getMessage());
        return [];
    }
}

function addSection($subjectId, $sectionName) {
    try {
        $pdo = getDBConnection();
        
        // Get the next sort order
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), -1) + 1 as next_order FROM sections WHERE subject_id = ?");
        $stmt->execute([$subjectId]);
        $nextOrder = $stmt->fetch()['next_order'];
        
        $stmt = $pdo->prepare("INSERT INTO sections (subject_id, section_name, sort_order) VALUES (?, ?, ?)");
        $stmt->execute([$subjectId, $sectionName, $nextOrder]);
        
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("Add section error: " . $e->getMessage());
        return false;
    }
}

function deleteSection($sectionId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM sections WHERE id = ?");
        return $stmt->execute([$sectionId]);
    } catch (PDOException $e) {
        error_log("Delete section error: " . $e->getMessage());
        return false;
    }
}

function getSectionById($sectionId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM sections WHERE id = ?");
        $stmt->execute([$sectionId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get section by ID error: " . $e->getMessage());
        return null;
    }
}

// =============================================
// STUDY CARD FUNCTIONS
// =============================================

function getStudyCards($subjectId, $sectionId = null) {
    try {
        $pdo = getDBConnection();
        
        if ($sectionId !== null) {
            $stmt = $pdo->prepare(
                "SELECT * FROM study_cards 
                 WHERE subject_id = ? AND section_id = ? 
                 ORDER BY sort_order, created_at"
            );
            $stmt->execute([$subjectId, $sectionId]);
        } else {
            $stmt = $pdo->prepare(
                "SELECT sc.*, s.section_name 
                 FROM study_cards sc
                 JOIN sections s ON sc.section_id = s.id
                 WHERE sc.subject_id = ? 
                 ORDER BY s.sort_order, sc.sort_order, sc.created_at"
            );
            $stmt->execute([$subjectId]);
        }
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Get study cards error: " . $e->getMessage());
        return [];
    }
}

function addStudyCard($subjectId, $sectionId, $title, $content, $cardType = 'normal') {
    try {
        $pdo = getDBConnection();
        
        // Get the next sort order
        $stmt = $pdo->prepare(
            "SELECT COALESCE(MAX(sort_order), -1) + 1 as next_order 
             FROM study_cards 
             WHERE subject_id = ? AND section_id = ?"
        );
        $stmt->execute([$subjectId, $sectionId]);
        $nextOrder = $stmt->fetch()['next_order'];
        
        $stmt = $pdo->prepare(
            "INSERT INTO study_cards (subject_id, section_id, title, content, card_type, sort_order) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([$subjectId, $sectionId, $title, $content, $cardType, $nextOrder]);
        
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("Add study card error: " . $e->getMessage());
        return false;
    }
}

function updateStudyCard($cardId, $title, $content) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE study_cards SET title = ?, content = ? WHERE id = ?");
        return $stmt->execute([$title, $content, $cardId]);
    } catch (PDOException $e) {
        error_log("Update study card error: " . $e->getMessage());
        return false;
    }
}

function deleteStudyCard($cardId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM study_cards WHERE id = ?");
        return $stmt->execute([$cardId]);
    } catch (PDOException $e) {
        error_log("Delete study card error: " . $e->getMessage());
        return false;
    }
}

function getStudyCardById($cardId) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM study_cards WHERE id = ?");
        $stmt->execute([$cardId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get study card by ID error: " . $e->getMessage());
        return null;
    }
}

// =============================================
// HELPER FUNCTIONS
// =============================================

function renderStudyCard($card) {
    $cardClass = $card['card_type'] === 'normal' ? 'review-card' : 'review-card ' . htmlspecialchars($card['card_type']);
    
    echo '<div class="' . $cardClass . '" style="position: relative;">';
    echo '<div class="card-actions" style="position: absolute; top: 5px; right: 5px;">';
    echo '<button class="btn btn-sm btn-outline-danger me-1" onclick="deleteCard(' . (int)$card['id'] . ')">&times;</button>';
    echo '<button class="btn btn-sm btn-outline-primary" onclick="editCard(' . (int)$card['id'] . ', \'' . htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') . '\', \'' . htmlspecialchars($card['content'], ENT_QUOTES, 'UTF-8') . '\')">&hellip;</button>';
    echo '</div>';
    echo '<h5 class="fw-bold">' . htmlspecialchars($card['title']) . '</h5>';
    
    // Allow HTML in content but sanitize it properly
    echo '<div>' . $card['content'] . '</div>';
    
    echo '</div>';
}
?>