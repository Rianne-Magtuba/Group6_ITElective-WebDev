<?php 
include "includes/db_functions.php";

// Handle add section request
if (isset($_POST["add_section"])) {
  $table = "study_cards_test";
  $sectionName = $_POST["section_name"];
  // use DB helper
  insertSection($table, $sectionName);
  header("Location: " . $_SERVER["PHP_SELF"]);
  exit;
}

// Handle delete section request
if (isset($_POST["delete_section"])) {
    $table = "study_cards_test";
    $sectionName = $_POST["section_name"];
    // delete all cards in the section
    deleteSection($table, $sectionName);
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

// Handle add card request
if (isset($_POST["add_card"])) {
    $table = "study_cards_test";
    $tabName = $_POST["tab_name"];
    $title = $_POST["title"];
    $content = $_POST["content"];
    $cardType = $_POST["card_type"] ?: "normal";
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO `$table` (tab_name, title, content, card_type, sort_order) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("ssss", $tabName, $title, $content, $cardType);
    $stmt->execute();
    $stmt->close();
    mysqli_close($conn);
    
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

// Handle delete request
if (isset($_POST["delete_id"])) {
    deleteStudyCard($_POST["delete_id"], "study_cards_test");
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}

// Handle edit request
if (isset($_POST["edit_id"])) {
    $title = urldecode($_POST["title"]);
    $content = urldecode($_POST["content"]);
    updateStudyCard($_POST["edit_id"], $title, $content, "study_cards_test");
    header("Location: " . $_SERVER["PHP_SELF"]);
    exit;
}
  
function getStudyCardsCustom($tab_name) {
    $conn = getDBConnection();
    $sql = "SELECT * FROM `study_cards_test` WHERE tab_name = \"" . mysqli_real_escape_string($conn, $tab_name) . "\" ORDER BY sort_order";
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

function getAllSections() {
    $conn = getDBConnection();
    $sql = "SELECT DISTINCT tab_name FROM `study_cards_test` ORDER BY tab_name";
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>test Template</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
  <link href="css/styles.css" rel="stylesheet" />
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-main fixed-top shadow-sm">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <i class="fa-solid fa-lightbulb fa-lg me-2"></i> CramTayo
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarMenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php">Subjects</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php">About</a></li>
      </ul>
      <div class="ms-lg-3 mt-3 mt-lg-0">
        <button id="signInBtn" class="btn btn-outline-light btn-nav rounded-pill ms-3"
                data-bs-toggle="modal" data-bs-target="#authMainModal">
          Sign In
        </button>
      </div>
    </div>
  </div>
</nav>

<div class="container mt-5 pt-5">
  <div class="text-center mb-4">
    <h1 class="fw-bold">test</h1>
    <p class="fst-italic text-muted">Study materials for test</p>
  </div>

  <?php $sections = getAllSections(); ?>
  
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
            $cards = getStudyCardsCustom($section);
            foreach ($cards as $card) {
                renderStudyCard($card);
            }
            ?>
              <div class="review-card add-card-btn" onclick="showAddCardModal('<?php echo $section;?>')" style="cursor: pointer; border: 2px dashed #28a745; display: flex; align-items: center; justify-content: center; min-height: 200px;">
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
    
    // Set the selected tab — prefer passed tabName, otherwise use active tab
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
  const activeBtn = document.querySelector('.nav-link.active');
  if (!activeBtn) {
    alert('No section selected to remove.');
    return;
  }
  const sectionId = (activeBtn.getAttribute('data-bs-target') || '').replace('#','');
  if (!sectionId) {
    alert('Unable to determine the current section.');
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
  nameInput.value = sectionId;
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