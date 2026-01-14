<?php 
include "includes/db_functions.php";
include "includes/subject_functions.php";

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// Get subject ID from URL parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$subjectId = intval($_GET['id']);

// Get subject details and verify ownership
$subject = getSubjectById($subjectId);

if (!$subject) {
    echo "<script>alert('Subject not found'); window.location.href='index.php';</script>";
    exit;
}

// Verify user owns this subject
if (!userOwnsSubject($subjectId, getCurrentUserId())) {
    echo "<script>alert('Access denied. You do not own this subject.'); window.location.href='index.php';</script>";
    exit;
}

$subjectName = $subject['display_name'];

// =============================================
// HANDLE POST REQUESTS
// =============================================

// Handle add section request
if (isset($_POST["add_section"])) {
    $sectionName = trim($_POST["section_name"]);
    if (!empty($sectionName)) {
        $newSectionId = addSection($subjectId, $sectionName);
        if ($newSectionId) {
            header("Location: subject_view.php?id=" . $subjectId);
            exit;
        } else {
            $error_message = "Failed to add section. It may already exist.";
        }
    }
}

// Handle delete section request
if (isset($_POST["delete_section"])) {
    $sectionId = intval($_POST["section_id"]);
    if (deleteSection($sectionId)) {
        header("Location: subject_view.php?id=" . $subjectId);
        exit;
    } else {
        $error_message = "Failed to delete section.";
    }
}

// Handle add card request
if (isset($_POST["add_card"])) {
    $sectionId = intval($_POST["section_id"]);
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);
    $cardType = $_POST["card_type"] ?: "normal";
    $imagePath = null;
    
    // Handle image upload
    if (isset($_FILES['card_image']) && $_FILES['card_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/cards/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileTmpPath = $_FILES['card_image']['tmp_name'];
        $fileName = $_FILES['card_image']['name'];
        $fileSize = $_FILES['card_image']['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions) && $fileSize <= 5 * 1024 * 1024) {
            // Generate unique filename
            $newFileName = 'card_' . getCurrentUserId() . '_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $imagePath = $destPath;
            }
        }
    }
    
    if (!empty($title) && !empty($content)) {
        $newCardId = addStudyCardWithImage($subjectId, $sectionId, $title, $content, $cardType, $imagePath);
        if ($newCardId) {
            header("Location: subject_view.php?id=" . $subjectId);
            exit;
        } else {
            $error_message = "Failed to add card.";
        }
    }
}

// Handle delete card request
if (isset($_POST["delete_id"])) {
    $cardId = intval($_POST["delete_id"]);
    
    // Get card to delete image file
    $card = getStudyCardById($cardId);
    
    if ($card && deleteStudyCard($cardId)) {
        // Delete image file if it exists
        if ($card['image_path'] && file_exists($card['image_path'])) {
            unlink($card['image_path']);
        }
        
        header("Location: subject_view.php?id=" . $subjectId);
        exit;
    } else {
        $error_message = "Failed to delete card.";
    }
}

// Handle edit card request
// Handle update card request
if (isset($_POST["update_card"])) {
    $cardId = intval($_POST["edit_card_id"]);
    $title = trim($_POST["edit_title"]);
    $content = trim($_POST["edit_content"]);
    $cardType = $_POST["edit_card_type"] ?: "normal";
    $currentImagePath = $_POST["current_image_path"];
    $imagePath = $currentImagePath; // Keep current image by default
    
    // Handle image removal
    if (isset($_POST["remove_image"]) && !empty($currentImagePath)) {
        if (file_exists($currentImagePath)) {
            unlink($currentImagePath);
        }
        $imagePath = null;
    }
    
    // Handle new image upload
    if (isset($_FILES['edit_card_image']) && $_FILES['edit_card_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/cards/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileTmpPath = $_FILES['edit_card_image']['tmp_name'];
        $fileName = $_FILES['edit_card_image']['name'];
        $fileSize = $_FILES['edit_card_image']['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions) && $fileSize <= 5 * 1024 * 1024) {
            // Delete old image if exists and not removed already
            if ($currentImagePath && file_exists($currentImagePath) && !isset($_POST["remove_image"])) {
                unlink($currentImagePath);
            }
            
            $newFileName = 'card_' . getCurrentUserId() . '_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $imagePath = $destPath;
            }
        }
    }
    
    if (!empty($title) && !empty($content)) {
        if (updateStudyCardFull($cardId, $title, $content, $cardType, $imagePath)) {
            header("Location: subject_view.php?id=" . $subjectId);
            exit;
        } else {
            $error_message = "Failed to update card.";
        }
    }
}

// Get all sections for this subject
$sections = getSections($subjectId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($subjectName); ?> - CramTayo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
  <link href="css/styles.css" rel="stylesheet" />
  <style>
    .review-card {
      position: relative;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .review-card:hover:not(.add-card-btn) {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    #viewCardModal .modal-dialog {
      max-width: 800px;
    }
    #viewCardModal .card-content {
      font-size: 1.1rem;
      line-height: 1.8;
      white-space: pre-wrap;
    }
    #viewCardModal .card-image-large {
      max-width: 100%;
      max-height: 500px;
      object-fit: contain;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
   .review-card {
  position: relative;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  padding-top: 40px; /* Space for buttons */
}

.review-card h5 {
  padding-right: 10px;
  word-wrap: break-word;
  overflow-wrap: break-word;
}
    /* Quiz Mode Styles */
    .quiz-flashcard {
      width: 100%;
      max-width: 700px;
      height: 450px;
      margin: 0 auto;
      perspective: 1000px;
      cursor: pointer;
    }
    
    .quiz-flashcard-inner {
      position: relative;
      width: 100%;
      height: 100%;
      text-align: center;
      transition: transform 0.6s;
      transform-style: preserve-3d;
    }
    
    .quiz-flashcard.flipped .quiz-flashcard-inner {
      transform: rotateY(180deg);
    }
    
    .quiz-flashcard-front,
    .quiz-flashcard-back {
      position: absolute;
      width: 100%;
      height: 100%;
      backface-visibility: hidden;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px;
      border-radius: 15px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.2);
      background: white;
    }
    
    .quiz-flashcard-front {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    
    .quiz-flashcard-back {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      color: white;
      transform: rotateY(180deg);
    }
    
    .quiz-flashcard-front h2 {
      margin-bottom: 20px;
      font-weight: bold;
      font-size: 2rem;
    }
    
    .quiz-flashcard-back h3 {
      margin-bottom: 20px;
      font-weight: bold;
    }
    
    .quiz-flashcard-back .card-content {
      max-height: 280px;
      overflow-y: auto;
      padding: 20px;
      background: rgba(255,255,255,0.2);
      border-radius: 10px;
      font-size: 1.1rem;
      line-height: 1.6;
      width: 100%;
      white-space: pre-wrap;
text-align: left;
    }
    
    .quiz-card-image {
      max-width: 90%;
      max-height: 200px;
      object-fit: contain;
      border-radius: 8px;
      margin-top: 15px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .quiz-controls {
      margin-top: 30px;
      display: flex;
      gap: 15px;
      justify-content: center;
      align-items: center;
      flex-wrap: wrap;
    }
    
    .quiz-progress {
      font-size: 1.3rem;
      font-weight: bold;
      color: #333;
      margin-bottom: 10px;
    }
    
    .quiz-hint {
      font-size: 1rem;
      color: rgba(255,255,255,0.9);
      margin-top: 15px;
      font-style: italic;
      animation: pulse 2s ease-in-out infinite;
    }
    
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.6; }
    }
    
    #quizModal .modal-dialog {
      max-width: 900px;
    }
    
    .quiz-controls .btn {
      min-width: 120px;
    }
      .hidden {
  display: none !important;
}
  </style>
</head>
<body>

<nav id="navbar" class="navbar navbar-expand-lg navbar-dark fixed-top shadow-sm" style="background-color: #55392e;">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <i class="fa-solid fa-lightbulb fa-lg me-2"></i> CramTayo
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarMenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="index.php#home">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#subjects">Subjects</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#ab">About</a></li>
      </ul>
      
      <div class="ms-lg-3 mt-3 mt-lg-0">
        <?php if (isLoggedIn()): ?>
          <span class="text-light me-3">Welcome, <?php echo htmlspecialchars(getCurrentUsername()); ?>!</span>
          <a href="index.php?logout=1" class="btn btn-outline-light rounded-pill">Sign Out</a>
        <?php else: ?>
          <button id="signInBtn" class="btn btn-outline-light rounded-pill ms-3" 
                  onclick="window.location.href='index.php'">
            Sign In
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<div class="container mt-5 pt-5">
  <div class="text-center mb-4">
    <h1 class="fw-bold"><?php echo htmlspecialchars($subjectName); ?></h1>
    <p class="fst-italic text-muted">Study materials for <?php echo htmlspecialchars($subjectName); ?></p>
  </div>

  <?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($error_message); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (empty($sections)): ?>
    <div class="text-center py-5">
      <h3 class="text-muted mb-3">No sections yet</h3>
      <p class="text-muted mb-4">Create your first section to get started</p>
      <button class="btn btn-success" onclick="showAddSectionModal()">+ Add Section</button>
    </div>
  <?php else: ?>
    <ul class="nav nav-tabs mb-3 custom-tabs" id="customTabs" role="tablist">
      <?php foreach ($sections as $index => $section): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?php echo $index === 0 ? "active" : ""; ?>" 
                  id="section-<?php echo $section['id']; ?>-tab" 
                  data-bs-toggle="tab" 
                  data-bs-target="#section-<?php echo $section['id']; ?>" 
                  data-section-id="<?php echo $section['id']; ?>"
                  type="button" role="tab">
            <?php echo htmlspecialchars(ucfirst($section['section_name'])); ?>
          </button>
        </li>
      <?php endforeach; ?>
      <li class="nav-item" role="presentation">
        <button class="btn btn-success btn-sm ms-2" onclick="showAddSectionModal()" type="button">+ Add Section</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="btn btn-danger btn-sm ms-2" onclick="removeSectionPrompt()" type="button">Remove Section</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="btn btn-primary btn-sm ms-2" onclick="startQuizMode()" type="button">
          <i class="fas fa-brain me-1"></i> Quiz Mode
        </button>
      </li>
    </ul>

    <div class="tab-content" id="customTabsContent">
      <?php foreach ($sections as $index => $section): ?>
        <div class="tab-pane fade <?php echo $index === 0 ? "show active" : ""; ?>" 
             id="section-<?php echo $section['id']; ?>" 
             role="tabpanel">
          <div class="review-grid">
            <?php
            $cards = getStudyCards($subjectId, $section['id']);
            foreach ($cards as $card) {
                renderStudyCardWithView($card);
            }
            ?>
            <div class="review-card add-card-btn" 
                 onclick="showAddCardModal(<?php echo $section['id']; ?>)" 
                 style="cursor: pointer; border: 2px dashed #28a745; display: flex; align-items: center; justify-content: center; min-height: 200px;">
              <div class="text-center">
                <i class="fas fa-plus fa-3x text-success mb-2"></i>
                <p class="text-success mb-0">Add New Card</p>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<!-- Section Selection Modal -->
<div class="modal fade" id="sectionSelectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-brain me-2"></i> Select Quiz Section
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3">Choose which section you'd like to be quizzed on:</p>
        <div class="list-group" id="sectionList">
          <button type="button" class="list-group-item list-group-item-action" onclick="selectQuizSection('all')">
            <i class="fas fa-layer-group me-2"></i> <strong>All Sections</strong>
            <span class="text-muted ms-2">(All cards)</span>
          </button>
          <?php foreach ($sections as $section): ?>
            <button type="button" class="list-group-item list-group-item-action" 
                    onclick="selectQuizSection(<?php echo $section['id']; ?>)">
              <i class="fas fa-folder me-2"></i> <?php echo htmlspecialchars(ucfirst($section['section_name'])); ?>
              <span class="text-muted ms-2">(<?php 
                $cardCount = count(getStudyCards($subjectId, $section['id']));
                echo $cardCount . ' card' . ($cardCount != 1 ? 's' : '');
              ?>)</span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Quiz Mode Modal -->
<div class="modal fade" id="quizModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-brain me-2"></i> Quiz Mode
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="resetQuiz()"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <div class="quiz-progress" id="quizProgress">Card 1 of 1</div>
        </div>
        
        <div class="quiz-flashcard" id="quizFlashcard" onclick="flipCard()">
          <div class="quiz-flashcard-inner" id="quizFlashcardInner">
            <div class="quiz-flashcard-front">
              <h2 id="quizCardTitle">Card Title</h2>
              <div class="quiz-hint">
                <i class="fas fa-hand-pointer me-2"></i>Click to flip
              </div>
            </div>
            <div class="quiz-flashcard-back">
              <h3>Answer</h3>
              <div class="card-content" id="quizCardContent">Card content goes here</div>
              <div id="quizCardImageContainer" style="display: none;">
                <img id="quizCardImage" src="" alt="Card image" class="quiz-card-image">
              </div>
              <div class="quiz-hint mt-3">
                <i class="fas fa-hand-pointer me-2"></i>Click to flip back
              </div>
            </div>
          </div>
        </div>
        
        <div class="quiz-controls">
          <button class="btn btn-secondary" onclick="previousCard()" id="prevBtn">
            <i class="fas fa-arrow-left me-1"></i> Previous
          </button>
          <button class="btn btn-primary" onclick="nextCard()" id="nextBtn">
            Next <i class="fas fa-arrow-right ms-1"></i>
          </button>
        </div>
        
        <div class="text-center mt-3">
          <button class="btn btn-sm btn-outline-secondary me-2" onclick="shuffleCards()">
            <i class="fas fa-shuffle me-1"></i> Shuffle
          </button>
          <button class="btn btn-sm btn-outline-info" onclick="resetQuiz()">
            <i class="fas fa-redo me-1"></i> Reset
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Section Modal -->
<div id="addSectionModal" class="hidden" style="
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    z-index: 3000;
    min-width: 300px;
">
  <p>Enter new section name:</p>
  <input type="text" id="newSectionName" placeholder="Section name" style="width: 100%; padding: 8px; margin-top: 5px; border-radius: 5px; border: 1px solid #ccc;">
  <div style="text-align: right; margin-top: 15px;">
    <button id="addSectionConfirm" style="margin-right:10px;">Add</button>
    <button id="addSectionCancel">Cancel</button>
  </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="hidden" style="
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    z-index: 3000;
    min-width: 300px;
">
  <p id="confirmMessage">Are you sure?</p>
  <div style="text-align: right; margin-top: 15px;">
    <button id="confirmYes" style="margin-right:10px;">Yes</button>
    <button id="confirmCancel">Cancel</button>
  </div>
</div>


<!-- View Card Modal -->
<div class="modal fade" id="viewCardModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title fw-bold" id="viewCardTitle"></h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="viewCardImageContainer" class="text-center mb-4" style="display: none;">
          <img id="viewCardImage" src="" alt="Card image" class="card-image-large">
        </div>
        <div id="viewCardContent" class="card-content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Add Card Modal -->
<div class="modal fade" id="addCardModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Card</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" enctype="multipart/form-data" id="addCardForm">
        <div class="modal-body">
          <input type="hidden" id="modal_section_id" name="section_id" value="">
          
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required />
          </div>
          
          <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea name="content" class="form-control" rows="4" required></textarea>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Card Image (Optional)</label>
            <input type="file" name="card_image" id="cardImage" accept="image/*" onchange="previewCardImage(event)" class="form-control">
            <small class="text-muted">Accepted formats: JPG, PNG, GIF (Max 5MB)</small>
          </div>
          
          <!-- Image Preview -->
          <div id="cardImagePreview" style="display: none;" class="mb-3 text-center">
            <img id="cardPreviewImg" src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 4px; border: 1px solid #ddd;">
            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="clearCardImage()">Remove Image</button>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Card Type</label>
            <select name="card_type" class="form-select">
              <option value="normal">Normal</option>
              <option value="long-card">Long Card</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_card" class="btn btn-primary">Add Card</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Card Modal -->
<div class="modal fade" id="editCardModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Card</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" enctype="multipart/form-data" id="editCardForm">
        <div class="modal-body">
          <input type="hidden" id="edit_card_id" name="edit_card_id" value="">
          <input type="hidden" id="edit_current_image" name="current_image_path" value="">
          
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="edit_title" id="edit_title" class="form-control" required />
          </div>
          
          <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea name="edit_content" id="edit_content" class="form-control" rows="4" required></textarea>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Card Image</label>
            <input type="file" name="edit_card_image" id="editCardImage" accept="image/*" onchange="previewEditCardImage(event)" class="form-control">
            <small class="text-muted">Accepted formats: JPG, PNG, GIF (Max 5MB). Leave empty to keep current image.</small>
          </div>
          
          <!-- Current Image Display -->
          <div id="editCurrentImagePreview" style="display: none;" class="mb-3">
            <label class="form-label">Current Image:</label>
            <div class="text-center">
              <img id="editCurrentImg" src="" alt="Current" style="max-width: 100%; max-height: 200px; border-radius: 4px; border: 1px solid #ddd;">
              <div class="mt-2">
                <label class="form-check-label">
                  <input type="checkbox" name="remove_image" id="removeImageCheck" class="form-check-input"> Remove current image
                </label>
              </div>
            </div>
          </div>
          
          <!-- New Image Preview -->
          <div id="editCardImagePreview" style="display: none;" class="mb-3 text-center">
            <label class="form-label">New Image Preview:</label>
            <img id="editCardPreviewImg" src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 4px; border: 1px solid #ddd;">
            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="clearEditCardImage()">Remove New Image</button>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Card Type</label>
            <select name="edit_card_type" id="edit_card_type" class="form-select">
              <option value="normal">Normal</option>
              <option value="long-card">Long Card</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="update_card" class="btn btn-primary">Update Card</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>
<script>
// Quiz Mode Variables
let quizCards = [];
let currentQuizIndex = 0;
let isFlipped = false;

function startQuizMode() {
  // Show section selection modal instead of directly starting quiz
  const modalEl = document.getElementById('sectionSelectModal');
  const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
  bsModal.show();
}
function selectQuizSection(sectionId) {
  // Close section selection modal
  const selectModal = bootstrap.Modal.getInstance(document.getElementById('sectionSelectModal'));
  selectModal.hide();
  
  // Collect all cards data
  const cardsData = <?php 
    echo json_encode(array_map(function($section) use ($subjectId) {
      return [
        'section_id' => $section['id'],
        'cards' => getStudyCards($subjectId, $section['id'])
      ];
    }, $sections));
  ?>;
  
  // Filter cards based on selection
  if (sectionId === 'all') {
    // Combine all cards from all sections
    quizCards = [];
    cardsData.forEach(sectionData => {
      if (sectionData.cards && sectionData.cards.length > 0) {
        sectionData.cards.forEach(card => {
          quizCards.push({
            id: card.id,
            title: card.title,
            content: card.content,
            image_path: card.image_path || ''
          });
        });
      }
    });
  } else {
    // Get cards from specific section
    const sectionData = cardsData.find(s => s.section_id == sectionId);
    
    if (!sectionData || !sectionData.cards || sectionData.cards.length === 0) {
      alert('No cards available in this section for quiz mode.');
      return;
    }
    
    quizCards = sectionData.cards.map(card => ({
      id: card.id,
      title: card.title,
      content: card.content,
      image_path: card.image_path || ''
    }));
  }
  
  if (quizCards.length === 0) {
    alert('No cards available for quiz mode.');
    return;
  }
  
  currentQuizIndex = 0;
  isFlipped = false;
  
  // Show the quiz modal
  const quizModalEl = document.getElementById('quizModal');
  const bsQuizModal = bootstrap.Modal.getOrCreateInstance(quizModalEl);
  bsQuizModal.show();
  
  // Display first card
  displayCurrentCard();
}
function displayCurrentCard() {
  if (quizCards.length === 0) return;
  
  const card = quizCards[currentQuizIndex];
  
  // Update progress
  document.getElementById('quizProgress').textContent = `Card ${currentQuizIndex + 1} of ${quizCards.length}`;
  
  // Update card content
  document.getElementById('quizCardTitle').textContent = card.title;
  document.getElementById('quizCardContent').textContent = card.content;
  
  // Handle image
  const imageContainer = document.getElementById('quizCardImageContainer');
  const imageElement = document.getElementById('quizCardImage');
  
  if (card.image_path && card.image_path !== '') {
    imageElement.src = card.image_path;
    imageContainer.style.display = 'block';
  } else {
    imageContainer.style.display = 'none';
  }
  
  // Reset flip state
  isFlipped = false;
  document.getElementById('quizFlashcard').classList.remove('flipped');
  
  // Update button states
  document.getElementById('prevBtn').disabled = currentQuizIndex === 0;
  document.getElementById('nextBtn').disabled = currentQuizIndex === quizCards.length - 1;
}

function flipCard() {
  isFlipped = !isFlipped;
  const flashcard = document.getElementById('quizFlashcard');
  
  if (isFlipped) {
    flashcard.classList.add('flipped');
  } else {
    flashcard.classList.remove('flipped');
  }
}

function nextCard() {
  if (currentQuizIndex < quizCards.length - 1) {
    currentQuizIndex++;
    displayCurrentCard();
  }
}

function previousCard() {
  if (currentQuizIndex > 0) {
    currentQuizIndex--;
    displayCurrentCard();
  }
}

function shuffleCards() {
  // Fisher-Yates shuffle
  for (let i = quizCards.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [quizCards[i], quizCards[j]] = [quizCards[j], quizCards[i]];
  }
  
  currentQuizIndex = 0;
  displayCurrentCard();
}

function resetQuiz() {
  currentQuizIndex = 0;
  isFlipped = false;
  document.getElementById('quizFlashcard').classList.remove('flipped');
}

// Keyboard navigation for quiz mode
document.addEventListener('keydown', function(event) {
  const quizModal = document.getElementById('quizModal');
  const isQuizOpen = quizModal.classList.contains('show');
  
  if (!isQuizOpen) return;
  
  if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
    event.preventDefault();
    nextCard();
  } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
    event.preventDefault();
    previousCard();
  } else if (event.key === ' ' || event.key === 'Enter') {
    event.preventDefault();
    flipCard();
  }
});

function viewCard(title, content, imagePath) {
  // Set title
  document.getElementById('viewCardTitle').textContent = title;
  
  // Set content
  document.getElementById('viewCardContent').textContent = content;
  
  // Handle image
  const imageContainer = document.getElementById('viewCardImageContainer');
  const imageElement = document.getElementById('viewCardImage');
  
  if (imagePath && imagePath !== '') {
    imageElement.src = imagePath;
    imageContainer.style.display = 'block';
  } else {
    imageContainer.style.display = 'none';
  }
  
  // Show modal
  const modalEl = document.getElementById('viewCardModal');
  const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
  bsModal.show();
}

function deleteCard(id, event) {
  event.stopPropagation();
  if (confirm("Are you sure you want to delete this card?")) {
    const form = document.createElement("form");
    form.method = "POST";
    form.innerHTML = "<input type=\"hidden\" name=\"delete_id\" value=\"" + id + "\">";
    document.body.appendChild(form);
    form.submit();
  }
}


function viewCardFromData(element) {
  const title = element.getAttribute('data-card-title');
  const content = element.getAttribute('data-card-content');
  const imagePath = element.getAttribute('data-card-image');
  
  viewCard(title, content, imagePath);
}

// New function to handle edit from button
function editCardFromButton(button) {
  const cardDiv = button.closest('.review-card');
  const cardId = cardDiv.getAttribute('data-card-id');
  
  // Use the AJAX approach to get full card data
  fetch('get_card_data.php?id=' + cardId)
    .then(response => response.json())
    .then(card => {
      document.getElementById('edit_card_id').value = card.id;
      document.getElementById('edit_title').value = card.title;
      document.getElementById('edit_content').value = card.content;
      document.getElementById('edit_card_type').value = card.card_type || 'normal';
      document.getElementById('edit_current_image').value = card.image_path || '';
      
      if (card.image_path && card.image_path !== '') {
        document.getElementById('editCurrentImg').src = card.image_path;
        document.getElementById('editCurrentImagePreview').style.display = 'block';
      } else {
        document.getElementById('editCurrentImagePreview').style.display = 'none';
      }
      
      document.getElementById('editCardImagePreview').style.display = 'none';
      document.getElementById('editCardImage').value = '';
      document.getElementById('removeImageCheck').checked = false;
      
      const modalEl = document.getElementById("editCardModal");
      const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
      bsModal.show();
    })
    .catch(error => {
      console.error('Error fetching card data:', error);
      alert('Failed to load card data');
    });
}


function editCard(id, title, content, event) {
  event.stopPropagation();
  
  fetch('get_card_data.php?id=' + id)
    .then(response => response.json())
    .then(card => {
      document.getElementById('edit_card_id').value = card.id;
      document.getElementById('edit_title').value = card.title;
      document.getElementById('edit_content').value = card.content;
      document.getElementById('edit_card_type').value = card.card_type || 'normal';
      document.getElementById('edit_current_image').value = card.image_path || '';
      
      if (card.image_path && card.image_path !== '') {
        document.getElementById('editCurrentImg').src = card.image_path;
        document.getElementById('editCurrentImagePreview').style.display = 'block';
      } else {
        document.getElementById('editCurrentImagePreview').style.display = 'none';
      }
      
      document.getElementById('editCardImagePreview').style.display = 'none';
      document.getElementById('editCardImage').value = '';
      document.getElementById('removeImageCheck').checked = false;
      
      const modalEl = document.getElementById("editCardModal");
      const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
      bsModal.show();
    })
    .catch(error => {
      console.error('Error fetching card data:', error);
      alert('Failed to load card data');
    });
}

function previewEditCardImage(event) {
  const file = event.target.files[0];
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      alert('File size must be less than 5MB');
      event.target.value = '';
      document.getElementById('editCardImagePreview').style.display = 'none';
      return;
    }
    
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!validTypes.includes(file.type)) {
      alert('Please select a valid image file (JPG, PNG, or GIF)');
      event.target.value = '';
      document.getElementById('editCardImagePreview').style.display = 'none';
      return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('editCardPreviewImg').src = e.target.result;
      document.getElementById('editCardImagePreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
  } else {
    document.getElementById('editCardImagePreview').style.display = 'none';
  }
}

function clearEditCardImage() {
  document.getElementById('editCardImage').value = '';
  document.getElementById('editCardImagePreview').style.display = 'none';
}

function showAddCardModal(sectionId) {
  document.getElementById('modal_section_id').value = sectionId;
  document.getElementById('addCardForm').reset();
  document.getElementById('modal_section_id').value = sectionId;
  document.getElementById('cardImagePreview').style.display = 'none';
  
  const modalEl = document.getElementById("addCardModal");
  const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
  bsModal.show();
}

function previewCardImage(event) {
  const file = event.target.files[0];
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      alert('File size must be less than 5MB');
      event.target.value = '';
      document.getElementById('cardImagePreview').style.display ='none';
      return;
    }
    
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!validTypes.includes(file.type)) {
      alert('Please select a valid image file (JPG, PNG, or GIF)');
      event.target.value = '';
      document.getElementById('cardImagePreview').style.display = 'none';
      return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('cardPreviewImg').src = e.target.result;
      document.getElementById('cardImagePreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
  } else {
    document.getElementById('cardImagePreview').style.display = 'none';
  }
}

function clearCardImage() {
  document.getElementById('cardImage').value = '';
  document.getElementById('cardImagePreview').style.display = 'none';
}

function showAddSectionModal() {
  const modal = document.getElementById('addSectionModal');
  const input = document.getElementById('newSectionName');
  const confirmBtn = document.getElementById('addSectionConfirm');
  const cancelBtn = document.getElementById('addSectionCancel');

  // Reset input
  input.value = '';

  // Show modal
  modal.classList.remove('hidden');
  input.focus();

  // Remove previous handlers to prevent duplicates
  confirmBtn.onclick = null;
  cancelBtn.onclick = null;

  // Add button
  confirmBtn.onclick = function() {
    const sectionName = input.value.trim();
    if (sectionName === '') {
      showNotification('Section name cannot be empty', 'error');
      input.focus();
      return;
    }

    // Submit form
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    const flag = document.createElement('input');
    flag.type = 'hidden';
    flag.name = 'add_section';
    flag.value = '1';
    form.appendChild(flag);

    const nameInput = document.createElement('input');
    nameInput.type = 'hidden';
    nameInput.name = 'section_name';
    nameInput.value = sectionName;
    form.appendChild(nameInput);

    document.body.appendChild(form);
    form.submit();

    modal.classList.add('hidden');
  };

  // Cancel button
  cancelBtn.onclick = function() {
    modal.classList.add('hidden');
  };
}


function removeSectionPrompt() {
  const activeTab = document.querySelector('.nav-link.active');
  if (!activeTab) {
    showNotification('No section is currently selected.', 'error');
    return;
  }

  const sectionId = activeTab.getAttribute('data-section-id');
  const sectionName = activeTab.textContent.trim();

  if (!sectionId) {
    showNotification('Unable to determine the current section.', 'error');
    return;
  }

  // Show custom modal instead of confirm()
  const modal = document.getElementById('confirmModal');
  const message = document.getElementById('confirmMessage');
  const yesBtn = document.getElementById('confirmYes');
  const cancelBtn = document.getElementById('confirmCancel');

  message.textContent = `Are you sure you want to remove the section "${sectionName}"? This will delete all cards in this section.`;

  // Show modal
  modal.classList.remove('hidden');

  // Remove old click handlers
  yesBtn.onclick = null;
  cancelBtn.onclick = null;

  // Yes button
  yesBtn.onclick = function() {
    modal.classList.add('hidden');

    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    const flag = document.createElement('input');
    flag.type = 'hidden';
    flag.name = 'delete_section';
    flag.value = '1';
    form.appendChild(flag);

    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'section_id';
    idInput.value = sectionId;
    form.appendChild(idInput);

    document.body.appendChild(form);
    form.submit();
  };

  // Cancel button
  cancelBtn.onclick = function() {
    modal.classList.add('hidden');
  };
}

</script>

</body>
</html>

<?php
// Updated renderStudyCard function with clickable card
function renderStudyCardWithView($card) {
    $cardClass = $card['card_type'] === 'normal' ? 'review-card' : 'review-card ' . htmlspecialchars($card['card_type']);
    
    $cardTitle = htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8');
    $cardContent = htmlspecialchars($card['content'], ENT_QUOTES, 'UTF-8');
    $cardImage = htmlspecialchars($card['image_path'] ?? '', ENT_QUOTES, 'UTF-8');
    
    echo '<div class="' . $cardClass . '" style="position: relative;" 
          data-card-id="' . (int)$card['id'] . '"
          data-card-title="' . $cardTitle . '"
          data-card-content="' . $cardContent . '"
          data-card-image="' . $cardImage . '"
          onclick="viewCardFromData(this)">';
    
    // Edit and delete buttons (right side)
    echo '<div class="card-actions" style="position: absolute; top: 5px; right: 5px; z-index: 10;">';
    echo '<button class="btn btn-sm btn-outline-danger me-1" onclick="event.stopPropagation(); deleteCard(' . (int)$card['id'] . ', event)">&times;</button>';
    echo '<button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); editCardFromButton(this)">&hellip;</button>';
    echo '</div>';
    
    echo '<h5 class="fw-bold">' . htmlspecialchars($card['title']) . '</h5>';
    
    // Display image if exists
    if (!empty($card['image_path']) && file_exists($card['image_path'])) {
        echo '<div class="card-image mb-3" style="text-align: center;">';
        echo '<img src="' . htmlspecialchars($card['image_path']) . '" alt="Card image" style="max-width: 100%; max-height: 300px; border-radius: 8px; object-fit: contain;">';
        echo '</div>';
    }
    
    // Display content
    echo '<div>' . nl2br(htmlspecialchars($card['content'])) . '</div>';
    
    echo '</div>';
}
?>