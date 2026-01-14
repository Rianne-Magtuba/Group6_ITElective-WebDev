<?php
include "includes/db_functions.php";
include "includes/subject_functions.php";

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No card ID provided']);
    exit;
}

$cardId = intval($_GET['id']);
$card = getStudyCardById($cardId);

if ($card) {
    echo json_encode($card);
} else {
    echo json_encode(['error' => 'Card not found']);
}
?>