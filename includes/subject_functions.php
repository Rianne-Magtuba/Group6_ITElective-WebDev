<?php
/**
 * Subject Functions Wrapper
 * This file provides backward compatibility and convenience functions
 */

require_once 'db_functions.php';

// =============================================
// SUBJECT FUNCTIONS (Wrapper for backward compatibility)
// =============================================

function getSubjects($userId = null) {
    return getSubjectsDb($userId);
}

function addSubject($subjectName, $displayName, $description, $imagePath, $userId) {
    return addSubjectDb($subjectName, $displayName, $description, $imagePath, $userId);
}

function deleteSubject($subjectId, $userId = null) {
    return deleteSubjectDb($subjectId, $userId);
}

function userOwnsSubject($subjectId, $userId) {
    return userOwnsSubjectDb($subjectId, $userId);
}

// =============================================
// SECTION HELPER FUNCTIONS
// =============================================

/**
 * Get all section names for a subject (for backward compatibility)
 */
function getAllSectionsForSubject($subjectId) {
    $sections = getSections($subjectId);
    return array_map(function($section) {
        return $section['section_name'];
    }, $sections);
}

/**
 * Insert a new section
 */
function insertSection($subjectId, $sectionName) {
    return addSection($subjectId, $sectionName);
}

/**
 * Delete a section by name (for backward compatibility)
 */
function deleteSectionByName($subjectId, $sectionName) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM sections WHERE subject_id = ? AND section_name = ?");
        $stmt->execute([$subjectId, $sectionName]);
        $section = $stmt->fetch();
        
        if ($section) {
            return deleteSection($section['id']);
        }
        return false;
    } catch (PDOException $e) {
        error_log("Delete section by name error: " . $e->getMessage());
        return false;
    }
}

// =============================================
// CARD HELPER FUNCTIONS
// =============================================

/**
 * Get study cards for a specific section (by section name - backward compatibility)
 */
function getStudyCardsForSubjects($subjectId, $sectionName) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT sc.* 
            FROM study_cards sc
            JOIN sections s ON sc.section_id = s.id
            WHERE sc.subject_id = ? AND s.section_name = ?
            ORDER BY sc.sort_order, sc.created_at
        ");
        $stmt->execute([$subjectId, $sectionName]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get study cards for section error: " . $e->getMessage());
        return [];
    }
}

/**
 * Add a card to a section (by section name - backward compatibility)
 */
/**
 * Add a card with image support
 */
function addCardWithImage($subjectId, $sectionName, $title, $content, $cardType = 'normal', $imagePath = null) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM sections WHERE subject_id = ? AND section_name = ?");
        $stmt->execute([$subjectId, $sectionName]);
        $section = $stmt->fetch();
        
        if (!$section) {
            $sectionId = addSection($subjectId, $sectionName);
        } else {
            $sectionId = $section['id'];
        }
        
        return addStudyCardWithImage($subjectId, $sectionId, $title, $content, $cardType, $imagePath);
        
    } catch (PDOException $e) {
        error_log("Add card with image error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update a study card
 */
function updateCard($cardId, $title, $content) {
    return updateStudyCardFull($cardId, $title, $content);
}

/**
 * Delete a study card
 */
function deleteCard($cardId) {
    return deleteStudyCard($cardId);
}
?>