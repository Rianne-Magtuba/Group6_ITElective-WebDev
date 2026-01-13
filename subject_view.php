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
if (isset($_POST["edit_id"])) {
    $cardId = intval($_POST["edit_id"]);
    $title = urldecode($_POST["title"]);
    $content = urldecode($_POST["content"]);
    
    if (updateStudyCard($cardId, $title, $content)) {
        header("Location: subject_view.php?id=" . $subjectId);
        exit;
    } else {
        $error_message = "Failed to update card.";
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>
<script>
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
  event.stopPropagation(); // Prevent card click
  if (confirm("Are you sure you want to delete this card?")) {
    const form = document.createElement("form");
    form.method = "POST";
    form.innerHTML = "<input type=\"hidden\" name=\"delete_id\" value=\"" + id + "\">";
    document.body.appendChild(form);
    form.submit();
  }
}

function editCard(id, title, content, event) {
  event.stopPropagation(); // Prevent card click
  const newTitle = prompt("Edit title:", title);
  if (newTitle !== null && newTitle.trim() !== "") {
    const newContent = prompt("Edit content:", content.replace(/<[^>]*>/g, ""));
    if (newContent !== null && newContent.trim() !== "") {
      const form = document.createElement("form");
      form.method = "POST";
      form.innerHTML = "<input type=\"hidden\" name=\"edit_id\" value=\"" + id + "\">" +
                      "<input type=\"hidden\" name=\"title\" value=\"" + encodeURIComponent(newTitle) + "\">" +
                      "<input type=\"hidden\" name=\"content\" value=\"" + encodeURIComponent(newContent) + "\">";
      document.body.appendChild(form);
      form.submit();
    }
  }
}

function showAddCardModal(sectionId) {
  // Set the section ID in the hidden input
  document.getElementById('modal_section_id').value = sectionId;
  
  // Reset form and preview
  document.getElementById('addCardForm').reset();
  document.getElementById('modal_section_id').value = sectionId; // Set again after reset
  document.getElementById('cardImagePreview').style.display = 'none';
  
  // Show the modal
  const modalEl = document.getElementById("addCardModal");
  const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
  bsModal.show();
}

function previewCardImage(event) {
  const file = event.target.files[0];
  if (file) {
    // Check file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
      alert('File size must be less than 5MB');
      event.target.value = '';
      document.getElementById('cardImagePreview').style.display = 'none';
      return;
    }
    
    // Check file type
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!validTypes.includes(file.type)) {
      alert('Please select a valid image file (JPG, PNG, or GIF)');
      event.target.value = '';
      document.getElementById('cardImagePreview').style.display = 'none';
      return;
    }
    
    // Show preview
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
  const sectionName = prompt("Enter new section name:");
  if (sectionName && sectionName.trim() !== "") {
    const form = document.createElement("form");
    form.method = "POST";
    form.style.display = "none";

    const flag = document.createElement("input");
    flag.type = "hidden";
    flag.name = "add_section";
    flag.value = "1";
    form.appendChild(flag);

    const nameInput = document.createElement("input");
    nameInput.type = "hidden";
    nameInput.name = "section_name";
    nameInput.value = sectionName.trim();
    form.appendChild(nameInput);

    document.body.appendChild(form);
    form.submit();
  }
}

function removeSectionPrompt() {
  const activeTab = document.querySelector('.nav-link.active');
  if (!activeTab) {
    alert('No section is currently selected.');
    return;
  }
  
  const sectionId = activeTab.getAttribute('data-section-id');
  const sectionName = activeTab.textContent.trim();
  
  if (!sectionId) {
    alert('Unable to determine the current section.');
    return;
  }
  
  const confirmed = confirm('Are you sure you want to remove the section "' + sectionName + '"? This will delete all cards in this section.');
  if (!confirmed) return;
  
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
}
</script>

</body>
</html>

<?php
// Updated renderStudyCard function with clickable card
function renderStudyCardWithView($card) {
    $cardClass = $card['card_type'] === 'normal' ? 'review-card' : 'review-card ' . htmlspecialchars($card['card_type']);
    
    // Prepare data for onclick
    $cardTitle = htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8');
    $cardContent = htmlspecialchars($card['content'], ENT_QUOTES, 'UTF-8');
    $cardImage = htmlspecialchars($card['image_path'] ?? '', ENT_QUOTES, 'UTF-8');
    
    echo '<div class="' . $cardClass . '" style="position: relative;" onclick="viewCard(\'' . $cardTitle . '\', \'' . $cardContent . '\', \'' . $cardImage . '\')">';
    
    // Edit and delete buttons (right side)
    echo '<div class="card-actions" style="position: absolute; top: 5px; right: 5px; z-index: 10;">';
    echo '<button class="btn btn-sm btn-outline-danger me-1" onclick="deleteCard(' . (int)$card['id'] . ', event)">&times;</button>';
    echo '<button class="btn btn-sm btn-outline-primary" onclick="editCard(' . (int)$card['id'] . ', \'' . $cardTitle . '\', \'' . $cardContent . '\', event)">&hellip;</button>';
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