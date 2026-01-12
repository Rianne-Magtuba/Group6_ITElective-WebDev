<?php 
include "includes/db_functions.php";
include "includes/subject_functions.php";

// Get subject ID from URL parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$subjectId = intval($_GET['id']);

// Get subject details
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subjectId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Subject not found'); window.location.href='index.php';</script>";
    exit;
}

$subject = $result->fetch_assoc();
$subjectName = $subject['display_name'];
$tableName = 'study_cards_' . preg_replace('/[^a-zA-Z0-9]/', '', $subject['subject_name']);

$stmt->close();
mysqli_close($conn);

// Handle add section request
if (isset($_POST["add_section"])) {
    $sectionName = $_POST["section_name"];
    insertSection($tableName, $sectionName);
    header("Location: subject_view.php?id=" . $subjectId);
    exit;
}

// Handle delete section request
if (isset($_POST["delete_section"])) {
    $sectionName = $_POST["section_name"];
    deleteSection($tableName, $sectionName);
    header("Location: subject_view.php?id=" . $subjectId);
    exit;
}

// Handle add card request
if (isset($_POST["add_card"])) {
    $tabName = $_POST["tab_name"];
    $title = $_POST["title"];
    $content = $_POST["content"];
    $cardType = $_POST["card_type"] ?: "normal";
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO `$tableName` (tab_name, title, content, card_type, sort_order) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("ssss", $tabName, $title, $content, $cardType);
    $stmt->execute();
    $stmt->close();
    mysqli_close($conn);
    
    header("Location: subject_view.php?id=" . $subjectId);
    exit;
}

// Handle delete request
if (isset($_POST["delete_id"])) {
    deleteStudyCard($_POST["delete_id"], $tableName);
    header("Location: subject_view.php?id=" . $subjectId);
    exit;
}

// Handle edit request
if (isset($_POST["edit_id"])) {
    $title = urldecode($_POST["title"]);
    $content = urldecode($_POST["content"]);
    updateStudyCard($_POST["edit_id"], $title, $content, $tableName);
    header("Location: subject_view.php?id=" . $subjectId);
    exit;
}

function getStudyCardsForSubject($tableName, $tab_name) {

    return getStudyCardsForSubjects($tableName, $tab_name);
}


$sections = getAllSectionsForSubject($tableName);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($subjectName); ?> Template</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
  <link href="css/styles.css" rel="stylesheet" />
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
      
      <!-- Dynamic Sign In/Out Button -->
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
                  id="<?php echo $section; ?>-tab" 
                  data-bs-toggle="tab" 
                  data-bs-target="#<?php echo $section; ?>" 
                  type="button" role="tab"><?php echo ucfirst($section); ?></button>
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
        <div class="tab-pane fade <?php echo $index === 0 ? "show active" : ""; ?>" id="<?php echo $section; ?>" role="tabpanel">
          <div class="review-grid">
            <?php
            $cards = getStudyCardsForSubject($tableName, $section);
            foreach ($cards as $card) {
                renderStudyCard($card);
            }
            ?>
              <div class="review-card add-card-btn" onclick="showAddCardModal('<?php echo $section; ?>')" style="cursor: pointer; border: 2px dashed #28a745; display: flex; align-items: center; justify-content: center; min-height: 200px;">
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

<!-- Add Card Modal (Bootstrap) -->
<div class="modal fade" id="addCardModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Card</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Section</label>
            <select id="tabNameSelect" name="tab_name" class="form-select"></select>
          </div>
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea name="content" class="form-control" rows="4" required></textarea>
          </div>
          <input type="hidden" name="card_type" value="normal" />
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
function deleteCard(id) {
  if (confirm("Are you sure you want to delete this card?")) {
    const form = document.createElement("form");
    form.method = "POST";
    form.innerHTML = "<input type=\"hidden\" name=\"delete_id\" value=\"" + id + "\">";
    document.body.appendChild(form);
    form.submit();
  }
}

function editCard(id, title, content) {
  const newTitle = prompt("Edit title:", title);
  if (newTitle !== null && newTitle.trim() !== "") {
    const newContent = prompt("Edit content:", content.replace(/<[^>]*>/g, ""));
    if (newContent !== null && newContent.trim() !== "") {
      const form = document.createElement("form");
      form.method = "POST";
      form.innerHTML = "<input type=\"hidden\" name=\"edit_id\" value=\"" + id + "\"><input type=\"hidden\" name=\"title\" value=\"" + encodeURIComponent(newTitle) + "\"><input type=\"hidden\" name=\"content\" value=\"" + encodeURIComponent(newContent) + "\">";
      document.body.appendChild(form);
      form.submit();
    }
  }
}

function showAddCardModal(tabName) {
  console.debug('showAddCardModal called with tabName:', tabName);
  // Update the dropdown with current sections
  const tabSelect = document.getElementById("tabNameSelect");
  if (!tabSelect) {
    console.error('showAddCardModal: tabNameSelect element not found');
  } else {
    // Clear existing options
    tabSelect.innerHTML = "";
    
    // Get all current tab buttons to rebuild dropdown
    const tabButtons = document.querySelectorAll("[data-bs-toggle=\"tab\"]");
    console.debug('showAddCardModal: found tab buttons count=', tabButtons.length);
    tabButtons.forEach((button, idx) => {
      const tabId = button.getAttribute("data-bs-target").replace("#", "");
      const tabText = button.textContent;
      const option = document.createElement("option");
      option.value = tabId;
      option.text = tabText;
      tabSelect.appendChild(option);
      console.debug(`showAddCardModal: appended option[${idx}]`, option.value, option.text);
    });
    
    // Set the selected tab – prefer passed tabName, otherwise use active tab
    if (tabName) {
      tabSelect.value = tabName;
      console.debug('showAddCardModal: selected tab set to', tabSelect.value);
    } else {
      const activeBtn = document.querySelector('.nav-link.active');
      if (activeBtn) {
        const activeId = (activeBtn.getAttribute('data-bs-target') || '').replace('#','');
        if (activeId) {
          tabSelect.value = activeId;
          console.debug('showAddCardModal: selected active tab', activeId);
        }
      }
    }
  }
  
  const modalEl = document.getElementById("addCardModal");
  if (!modalEl) {
    console.error('showAddCardModal: addCardModal element not found');
  } else if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
    // Fallback if bootstrap JS not available
    modalEl.classList.add('show');
    modalEl.style.display = 'block';
    console.debug('showAddCardModal: bootstrap not found, fallback display used');
  } else {
    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    bsModal.show();
    console.debug('showAddCardModal: bootstrap modal shown');
  }
}

function closeAddCardModal() {
  const modalEl = document.getElementById("addCardModal");
  if (!modalEl) return;
  if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
    const bs = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
    bs.hide();
  } else {
    modalEl.classList.remove('show');
    modalEl.style.display = 'none';
  }
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
    nameInput.value = sectionName.toLowerCase().replace(/[^a-z0-9]/g, "");
    form.appendChild(nameInput);

    document.body.appendChild(form);
    form.submit();
  }
}

function removeSectionPrompt() {
  console.debug('removeSectionPrompt called');
  // Try to get active tab button first
  let sectionId = '';
  const activeBtn = document.querySelector('.nav-link.active');
  if (activeBtn) {
    sectionId = (activeBtn.getAttribute('data-bs-target') || '').replace('#','');
  }
  // Fallback: find active tab-pane
  if (!sectionId) {
    const activePane = document.querySelector('.tab-pane.show.active');
    if (activePane) sectionId = activePane.id || '';
  }
  if (!sectionId) {
    alert('Unable to determine the current section to remove.');
    return;
  }
  const confirmed = confirm('Are you sure you want to remove the section? Removing the section removes all the cards present in it');
  if (!confirmed) return;
  const form = document.createElement('form');
  form.method = 'POST';
  form.style.display = 'none';
  const flag = document.createElement('input');
  flag.type = 'hidden';
  flag.name = 'delete_section';
  flag.value = '1';
  form.appendChild(flag);
  const nameInput = document.createElement('input');
  nameInput.type = 'hidden';
  nameInput.name = 'section_name';
  // sanitize: remove leading # and trim
  nameInput.value = sectionId.toString().replace(/^#+/, '').trim();
  form.appendChild(nameInput);
  document.body.appendChild(form);
  form.submit();
}

function showAddCardModalWithSection(sectionName) {
  console.debug('showAddCardModalWithSection called with', sectionName);
  const modalEl = document.getElementById("addCardModal");
  if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
    const bs = bootstrap.Modal.getOrCreateInstance(modalEl);
    bs.show();
  } else if (modalEl) {
    modalEl.style.display = 'block';
  }
  const tabSelect = document.getElementById("tabNameSelect");
  
  // Add new option if it doesn't exist
  let optionExists = false;
  for (let i = 0; i < tabSelect.options.length; i++) {
    if (tabSelect.options[i].value === sectionName) {
      optionExists = true;
      break;
    }
  }
  
  if (!optionExists) {
    const newOption = document.createElement("option");
    newOption.value = sectionName;
    newOption.text = sectionName.charAt(0).toUpperCase() + sectionName.slice(1);
    tabSelect.appendChild(newOption);
  }
  
  tabSelect.value = sectionName;
}
</script>

</body>
</html>