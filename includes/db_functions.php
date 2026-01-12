<?php
// Start session at the beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$DBHost = "localhost";
$DBUser = "root";
$DBPass = "";
$DBName = "cramtayo_db";

function getDBConnection() {
    global $DBHost, $DBUser, $DBPass, $DBName;
    $conn = new mysqli($DBHost, $DBUser, $DBPass, $DBName);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// =============================================
// SESSION & USER FUNCTIONS
// =============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function getCurrentUsername() {
    return isset($_SESSION['username']) ? $_SESSION['username'] : null;
}

function logoutUser() {
    session_unset();
    session_destroy();
}

// =============================================
// USER ACCOUNT FUNCTIONS
// =============================================

function registerAccount($username, $email, $password) {
    $conn = getDBConnection();

    // Check if email already exists
    $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $checkStmt->close();
        $conn->close();
        return false; // Email already exists
    }
    $checkStmt->close();

    // Insert new user
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $conn->close();
        return false;
    }

    $stmt->bind_param("sss", $username, $email, $password);
    
    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        return false;
    }

    $stmt->close();
    $conn->close();
    return true;
}

function loginAccount($email, $password) {
    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $email;
            
            $stmt->close();
            $conn->close();
            return true;
        }
    }

    $stmt->close();
    $conn->close();
    return false;
}

// =============================================
// SUBJECT STUDY CARD FUNCTIONS
// =============================================

function getStudyCardsForSubjects($tableName, $tab_name) {
    $conn = getDBConnection();
    $sql = "SELECT * FROM `" . $tableName . "` WHERE tab_name = '" . mysqli_real_escape_string($conn, $tab_name) . "' ORDER BY sort_order";
    $result = mysqli_query($conn, $sql);
    
    $cards = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cards[] = $row;
        }
    }
    
    mysqli_close($conn);
    return $cards;
}

function addCard($tableName, $tabName, $title, $content, $cardType) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO `$tableName` (tab_name, title, content, card_type, sort_order) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("ssss", $tabName, $title, $content, $cardType);
    $stmt->execute();
    $stmt->close();
    mysqli_close($conn);
}


function getAllSectionsForSubject($tableName) {
    $conn = getDBConnection();
    $sql = "SELECT DISTINCT tab_name FROM `" . $tableName . "` ORDER BY tab_name";
    $result = mysqli_query($conn, $sql);
    
    $sections = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $sections[] = $row["tab_name"];
        }
    }
    
    mysqli_close($conn);
    return $sections;
}

function deleteStudyCard($id, $table) {
    $conn = getDBConnection();
    $sql = "DELETE FROM `" . $table . "` WHERE id = " . intval($id);
    $result = mysqli_query($conn, $sql);
    mysqli_close($conn);
    return $result;
}

function updateStudyCard($id, $title, $content, $table) {
    $conn = getDBConnection();
    $title = mysqli_real_escape_string($conn, $title);
    $content = mysqli_real_escape_string($conn, $content);
    $sql = "UPDATE `" . $table . "` SET title = '$title', content = '$content' WHERE id = " . intval($id);
    $result = mysqli_query($conn, $sql);
    mysqli_close($conn);
    return $result;
}

function insertSection($table, $sectionName) {
    $conn = getDBConnection();
    $placeholderTitle = '__section_placeholder';
    $stmt = $conn->prepare("INSERT INTO `$table` (tab_name, title, content, card_type, sort_order) VALUES (?, ?, '', 'normal', 0)");
    if ($stmt === false) {
        mysqli_close($conn);
        return false;
    }
    $stmt->bind_param("ss", $sectionName, $placeholderTitle);
    $res = $stmt->execute();
    $stmt->close();
    mysqli_close($conn);
    return $res;
}

function deleteSection($table, $sectionName) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM `$table` WHERE tab_name = ?");
    if ($stmt === false) {
        mysqli_close($conn);
        return false;
    }
    $stmt->bind_param("s", $sectionName);
    $res = $stmt->execute();
    $stmt->close();
    mysqli_close($conn);
    return $res;
}

function renderStudyCard($card) {
    if (isset($card['title']) && $card['title'] === '__section_placeholder') {
        return;
    }
    $cardClass = $card['card_type'] === 'normal' ? 'review-card' : 'review-card ' . $card['card_type'];
    
    echo '<div class="' . $cardClass . '" style="position: relative;">';
    echo '<div class="card-actions" style="position: absolute; top: 5px; right: 5px;">';
    echo '<button class="btn btn-sm btn-outline-danger me-1" onclick="deleteCard(' . $card['id'] . ')">&times;</button>';
    echo '<button class="btn btn-sm btn-outline-primary" onclick="editCard(' . $card['id'] . ', \'' . htmlspecialchars($card['title'], ENT_QUOTES) . '\', \'' . htmlspecialchars($card['content'], ENT_QUOTES) . '\')">&hellip;</button>';
    echo '</div>';
    echo '<h5 class="fw-bold">' . htmlspecialchars($card['title']) . '</h5>';
    
    if (strpos($card['content'], '<') !== false) {
        echo $card['content'];
    } else {
        echo '<p>' . htmlspecialchars($card['content']) . '</p>';
    }
    
    echo '</div>';
}
?>