<?php 
include 'includes/db_functions.php';
include 'includes/subject_functions.php';

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
    header("Location: index.php");
    exit();
}

// =============================================
// PHP HANDLERS
// =============================================

// REGISTER HANDLER
if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $rawPassword = $_POST['password'];
    
    if(empty($username)){
       echo "<script>alert('Username is required'); window.location.href = 'index.php';</script>";
       exit();
    } else if(empty($email)){
       echo "<script>alert('Email is required'); window.location.href = 'index.php';</script>";
       exit();
    } else if(empty($rawPassword)){
       echo "<script>alert('Password is required'); window.location.href = 'index.php';</script>";
       exit();
    }
    
    $password = password_hash($rawPassword, PASSWORD_DEFAULT);
    
    if(registerAccount($username, $email, $password)){
      echo "<script>
  document.addEventListener('DOMContentLoaded', function () {
    showNotification('Registration successful! Please login.', 'success');
    setTimeout(function () {
      window.location.href = 'index.php';
    }, 2000);
  });
</script>";

    } else {
        echo "<script>
          document.addEventListener('DOMContentLoaded', function () {
            showNotification('Registration failed. Email may already be in use.', 'error');
          });
        </script>";
    }
}

// LOGIN HANDLER
if (isset($_POST['login'])) {
    $email = trim($_POST['loginEmail']);
    $password = $_POST['loginPass'];

    if (empty($email)) {
        $loginError = "Email is required.";
    } 
    else if (empty($password)) {
        $loginError = "Password is required.";
    } 
    else if (!loginAccount($email, $password)) {
        $loginError = "Invalid email or password.";
    } 
    else {
        echo "<script>window.location.href='index.php';</script>";
        exit();
    }
}

// DELETE SUBJECT HANDLER
if (isset($_POST['delete_subject'])) {
    if (!isLoggedIn()) {
       echo "<script>
                showNotification('Please login first.', 'error');
                setTimeout(() => { window.location.href = 'index.php'; }, 3000);
              </script>";
        exit();
    }
    
    $subjectId = intval($_POST['delete_subject']);
    
    // Get subject to delete image file
    $subject = getSubjectById($subjectId);
    
    if ($subject && deleteSubject($subjectId, getCurrentUserId())) {
        // Delete image file if it's not the default
        if ($subject['image_path'] && 
            $subject['image_path'] !== 'C:/xampp/htdocs/repos/webdev/img/default-card.jpg' && 
            file_exists($subject['image_path'])) {
            unlink($subject['image_path']);
        }
        echo "<script>
  document.addEventListener('DOMContentLoaded', function () {
    showNotification('Subject deleted successfully!', 'success');
    setTimeout(function () {
      window.location.href = 'index.php';
    }, 2000);
  });
</script>";


    } else {
         
           echo "<script>
          document.addEventListener('DOMContentLoaded', function () {
            showNotification('Failed to delete subject. You may not own this subject.', 'error');
          });
        </script>";
    }
}

// ADD SUBJECT HANDLER
if (isset($_POST['add_subject'])) {
    if (!isLoggedIn()) {
         echo "<script>
                showNotification('Please login first.', 'error');
                setTimeout(() => { window.location.href = 'index.php'; }, 3000);
              </script>";
        exit();
    }
    
    $displayName = trim($_POST['display_name']);
    $description = trim($_POST['description']);
    $subjectName = preg_replace('/[^a-zA-Z0-9\s]/', '', $displayName);
    $subjectName = preg_replace('/\s+/', '', $subjectName);
            $uploadDir = 'uploads/subjects/';
    // Default image path
   $imagePath = 'img/default-card.jpg';
    
    // Handle file upload
    if (isset($_FILES['subject_image']) && $_FILES['subject_image']['error'] === UPLOAD_ERR_OK) {

        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileTmpPath = $_FILES['subject_image']['tmp_name'];
        $fileName = $_FILES['subject_image']['name'];
        $fileSize = $_FILES['subject_image']['size'];
        $fileType = $_FILES['subject_image']['type'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Allowed extensions
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            // Check file size (5MB max)
            if ($fileSize <= 5 * 1024 * 1024) {
                // Generate unique filename
                $newFileName = 'subject_' . getCurrentUserId() . '_' . time() . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;
                
                // Move file to uploads directory
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $imagePath = $destPath;
                } else {
                     echo "<script>showNotification('Error uploading image. Using default image.', 'error');</script>";
                }
            } else {
                 echo "<script>showNotification('File size exceeds 5MB. Using default image.', 'error');</script>";
            }
        } else {
            echo "<script>showNotification('Invalid file type. Using default image.', 'error');</script>";
        }
    }
    
    $subjectId = addSubject($subjectName, $displayName, $description, $imagePath, getCurrentUserId());
    if ($subjectId) {
         echo "<script>
  document.addEventListener('DOMContentLoaded', function () {
    showNotification('Subject created successfully!', 'success');
    setTimeout(function () {
      window.location.href = 'index.php';
    }, 2000);
  });
</script>";

    } else {
        echo "<script>
  document.addEventListener('DOMContentLoaded', function () {
    showNotification('Failed to create subject.', 'error');
    setTimeout(function () {
      window.location.href = 'index.php';
    }, 2000);
  });
</script>";

    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CramTayo - Study Helper</title>
   
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" >

    <link href="css/styles.css" rel="stylesheet" />
    <style>

      /* Hidden */
.notify.hidden {
  opacity: 0;
  pointer-events: none;
  transform: translate(-50%, -10px); /* moves slightly up when hidden */
}
    .hidden {
  display: none !important;
}

    </style>
  </head>
 
<body>
  <!-- Navbar -->
<nav id="navbar" class="navbar navbar-expand-lg navbar-light fixed-top shadow-sm">
  <div class="container">

    <a class="navbar-brand d-flex align-items-center" href="#">
      <i class="fa-solid fa-lightbulb fa-lg me-2"></i> CramTayo
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center">

        <li class="nav-item">
          <a class="nav-link active" href="#home">Home</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="#subjects">Subjects</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="#ab">About</a>
        </li>

        <?php if (isLoggedIn()): ?>

          <li class="nav-item">
            <span class="nav-link disabled">
              Welcome, <?php echo htmlspecialchars(getCurrentUsername()); ?>!
            </span>
          </li>

          <li class="nav-item ms-lg-2">
            <a href="?logout=1" id="signInBtn" class="btn btn-outline-light rounded-pill">
              Sign Out
            </a>
          </li>

        <?php else: ?>

          <li class="nav-item ms-lg-3">
            <button id="signInBtn"
              class="btn btn-outline-light rounded-pill"
              data-bs-toggle="modal"
              data-bs-target="#loginEmailModal">
              Sign In
            </button>
          </li>

        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>



  <!-- Photo Section -->
<div id="home" class="photo-section d-flex align-items-center justify-content-center" style="background-image: url('img/crammingcover.jpeg')">
  <div class="photo-text text-center">
    <h1 class="display-4 fw-bold mb-3">Let's Review with CramTayo!</h1>
    <p class="lead mb-5">where you review, last minute.</p>

    <!-- search bar -->
    <form class="d-flex justify-content-center" role="search" onsubmit="return handleSearch(event)">
      <div class="search-wrapper">
        <input id="searchInput" class="form-control rounded-pill me-2" type="search" placeholder="Search subjects..." aria-label="Search" autocomplete="off" oninput="searchSubjects(this.value)">
        <div id="searchSuggestions"></div>
      </div>
      <button class="btn btn-outline-light rounded-pill" type="submit">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
    </form>
  </div>
</div>


<!-- Cards Section -->
<div id="subjects" class="cards-section py-5 bgColor">
  <div class="container text-center mb-5">
    <h2 class="fw-bold cardTxt">Subjects</h2>
    <p class="lead cardTxt">What's there to review?</p>
  </div>

  <div class="container">
    <div class="row g-5 justify-content-center">
      
      <?php
      // Get subjects based on login status
      if (isLoggedIn()) {
          $subjects = getSubjects(getCurrentUserId());
      } else {
          $subjects = []; // No subjects for logged out users
      }
      
   foreach ($subjects as $subject) {
    echo '<div class="col-md-6 col-lg-4 fade-in" id="subject-' . $subject['id'] . '" data-subject-name="' . htmlspecialchars(strtolower($subject['display_name'])) . '">';
    echo '  <div class="card shadow-sm h-100 position-relative">';
    echo '    <button onclick="deleteSubjectCard(' . $subject['id'] . ')" style="position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.8); border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; z-index: 10;">&times;</button>';
    
    // Image with error handling
    $imageSrc = htmlspecialchars($subject['image_path']);
    echo '    <img src="' . $imageSrc . '" class="card-img-top" alt="' . htmlspecialchars($subject['display_name']) . '" onerror="this.src=\'C:/xampp/htdocs/repos/webdev/img/default-card.jpg\'" style="height: 200px; object-fit: cover;">';
    
    echo '    <div class="card-body text-justify">';
    echo '      <h5 class="card-title text-center cardTxt">' . htmlspecialchars($subject['display_name']) . '</h5>';
    echo '      <p class="card-text text-center cardTxt">' . htmlspecialchars($subject['description']) . '</p>';
    echo '      <div class="d-flex justify-content-center">';
    echo '        <a href="subject_view.php?id=' . $subject['id'] . '" class="btn btn-primary">View</a>';
    echo '      </div>';
    echo '    </div>';
    echo '  </div>';
    echo '</div>';
}
      ?>

      <!-- Add Subject Card -->
        <div class="col-md-6 col-lg-4 fade-in">
        <?php if (isLoggedIn()): ?>
          <div class="card shadow-sm h-100 border-dashed" style="border: 2px dashed #ccc; cursor: pointer;" onclick="showAddSubjectModal()">
            <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
              <i class="fas fa-plus fa-4x mb-3 text-muted"></i>
              <h5 class="card-title cardTxt">Add New Subject</h5>
              <p class="card-text text-muted">Click to create a new study subject</p>
            </div>
          </div>
        <?php else: ?>
          <div class="card shadow-sm h-100 border-dashed" style="border: 2px dashed #ccc;">
            <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
              <i class="fas fa-lock fa-4x mb-3 text-muted"></i>
              <h5 class="card-title cardTxt">Login to Add Subjects</h5>
              <p class="card-text text-muted">Sign in to create your own study materials</p>
              <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#loginEmailModal">
                Sign In
              </button>
            </div>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>


<!-- Carousel + Text Section -->
<div id="caro" class="carousel-text-section py-5 fade-in">
  <div class="container">
    <div class="row align-items-center">
      
      <!-- Carousel Column -->
      <div class="col-lg-6 mb-4 mb-lg-0">
        <div id="carouselExampleSlide" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
          <div class="carousel-inner">
            <div class="carousel-item active">
              <img src="img/caro1.jpeg" class="d-block w-100" alt="caro1" >
            </div>
            <div class="carousel-item">
              <img src="img/car2.jpeg" class="d-block w-100" alt="caro2" >
            </div>
            <div class="carousel-item">
              <img src="img/caro3.jpg" class="d-block w-100" alt="caro3" >
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleSlide" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleSlide" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      </div>
      
      <!-- Text Column -->
      <div class="col-lg-6">
        <h2 class="fw-bold mb-3">Did you know?</h2>
        <p class="lead mb-4">Cramming has negative effects on students, including increased stress and anxiety, decreased long-term retention, and poor sleep. 
          However, It can also be effective for remembering a large volume of facts or details for a very brief period. Does it actually help you?</p>
        <a href="https://www.topuniversities.com/blog/does-cramming-your-exams-actually-work" class="btn btn-primary btn-lg">Find out more</a>
      </div>

    </div>
  </div>
</div>

<!-- About Us Section -->
<div id="ab" class="about py-5 fade-in">
  <div class="container text-center">

    <!-- Section Title -->
    <h2 class="about-title fw-bold mb-2">About Us</h2>
    <div class="about-divider mx-auto mb-4"></div>

    <!-- Description -->
    <p class="about-desc mb-5">
      This is a web site project for  Web Development.
    </p>

    <!-- Team Members -->
    <div class="row justify-content-center g-4">

      <div class="col-6 col-md-4 col-lg-2">
        <div class="team-member text-center">
          <i class="fas fa-user-circle fa-4x mb-2"></i>
          <h6 class="fw-bold mb-0">Rhaniel Dimaguila</h6>
        </div>
      </div>

      <div class="col-6 col-md-4 col-lg-2">
        <div class="team-member text-center">
          <i class="fas fa-user-circle fa-4x mb-2"></i>
          <h6 class="fw-bold mb-0">Riyle Lhane Mapanoo</h6>
        </div>
      </div>

      <div class="col-6 col-md-4 col-lg-2">
        <div class="team-member text-center">
          <i class="fas fa-user-circle fa-4x mb-2"></i>
          <h6 class="fw-bold mb-0">Rianne Magtuba</h6>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Footer -->
<footer class="footer py-5 fade-in">
  <div class="container">
    <div class="row align-items-start">

      <!-- Left Side: Brand -->
      <div class="col-md-5 mb-4 mb-md-0 d-flex align-items-center">
        <h5 class="footer-brand d-flex align-items-center mb-0">
          <i class="fa-solid fa-lightbulb me-3"></i> CramTayo
        </h5>
      </div>

      <!-- Separator Column -->
      <div class="col-md-1 d-none d-md-flex justify-content-center">
        <div class="footer-separator"></div>
      </div>

      <!-- Right Side: Get in Touch -->
      <div class="col-md-6 text-start">
        <p class="fw-bold mb-2 mt-2">Get in Touch</p>
        <ul class="list-unstyled footer-contact">
          <li class="mb-2 mt-1">
            <i class="fab fa-facebook me-2"></i> Facebook
          </li>
          <li class="mb-2 mt-1">
            <i class="fab fa-instagram me-2"></i> Instagram
          </li>
          <li class="mb-2 mt-1">
            <i class="fas fa-phone me-2"></i> Phone
          </li>
          <li class="mb-2 mt-1">
            <i class="fas fa-envelope me-2"></i> Email
          </li>
        </ul>
      </div>

    </div>
  </div>
</footer>

<!-- Notification Popup -->
<div id="notify" class="notify hidden">
  <span id="notifyMessage"></span>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="hidden" style="
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    color: #000;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    z-index: 3000;
    min-width: 300px;
">
  
</div>




<!-- LOGIN WITH EMAIL MODAL -->
<div class="modal fade" id="loginEmailModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-wide">
    <div class="modal-content p-4">

       <div class="modal-header border-0 pb-1">
        <h5 class="modal-title fw-bold">Welcome to CramTayo!</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body px-3">

        <label class="form-label mt-2">Email Address</label>
              <form action="" method="post"> 
        <input type="email" name = "loginEmail" class="form-control mb-4 py-2"   required
       pattern="^[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}$"
       title="Please enter a valid email (example@domain.com)">

      <label class="form-label">Password</label>
<div class="position-relative mb-4">
  <input type="password" name="loginPass" id="loginPass" class="form-control py-2">
  <button type="button" id="togglePassword" class="btn position-absolute"
          style="top: 50%; right: 10px; transform: translateY(-50%); padding: 0.25rem 0.5rem;">
    <i class="bi bi-eye"></i>
 
  </button>
</div>


<!-- ERROR MESSAGE -->
<?php if (!empty($loginError)) : ?>
  <div class="alert alert-danger py-2 mb-3">
    <?= $loginError ?>
  </div>
<?php endif; ?>

<button type="submit" name="login" class="btn btn-primary w-100 py-2 mb-3">
  Log In
</button>
        </form>

        <p class="text-center mt-3">
          Don't have an account?
          <a href="#" class="importantTxtColors fw-bold"
             data-bs-target="#registerEmailModal" data-bs-toggle="modal">
            Sign Up!
          </a>
        </p>

      </div>

    </div>
  </div>
</div>

<!-- REGISTER MODAL -->
<div class="modal fade" id="registerEmailModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">

      <div class="modal-header border-0 pb-1">
        <h5 class="modal-title fw-bold">Welcome to CramTayo!</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
      <form action="" method="post"> 
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control mb-3" required minlength="3">



        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control mb-3" required 
       pattern="^[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}$"
       title="Please enter a valid email (example@domain.com)">

      <label class="form-label">Password</label>
<div class="position-relative mb-4">
  <input type="password" name="password" id="registerPass" class="form-control py-2" required>
  
  <button type="button" id="toggleRegisterPassword" class="btn position-absolute"
          style="top: 50%; right: 10px; transform: translateY(-50%); padding: 0.25rem 0.5rem;">
    <i class="bi bi-eye"></i>
   
  </button>
</div>


        <button type="submit" name="register" class="btn btn-success w-100 mb-3">Register</button>
      </form>
        <p class="text-center small">
          Already have an account?
          <a href="#" class="importantTxtColors"
             data-bs-target="#loginEmailModal" data-bs-toggle="modal">
            Sign In!
          </a>
        </p>

        <hr>

        <p class="text-muted small text-center ">
          By signing up, you agree to CramTayo's
          <a href="#" class="importantTxtColors">Terms of Use</a> and <a href="#" class="importantTxtColors">Privacy Policy</a>.
        </p>

      </div>
    </div>
  </div>
</div>

<!-- ADD SUBJECT MODAL -->
<div id="addSubjectModal" style="display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
  <div style="position: relative; margin: 5% auto; width: 90%; max-width: 500px; background-color: white; border-radius: 8px; padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h5 style="margin: 0; font-weight: bold;">Add New Subject</h5>
      <button onclick="closeAddSubjectModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
    </div>
    
    <form id="addSubjectForm" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="add_subject" value="1">
      
  <label style="display: block; margin-bottom: 5px; font-weight: bold;">Subject Name:</label>
<input type="text" name="display_name" id="displayName" placeholder="e.g., Systems Integration and Architecture" maxlength="30" required style="width: 100%; padding: 8px; margin-bottom: 5px; border: 1px solid #ccc; border-radius: 4px;">
<div id="subjectNameCharCounter" style="text-align: right; font-size: 0.85rem; color: #666; margin-bottom: 15px;">0/30</div>
      
      <label style="display: block; margin-bottom: 5px; font-weight: bold;">Description:</label>
      <textarea name="description" id="description" placeholder="Brief description of the subject" required style="width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; height: 80px; resize: vertical;"></textarea>
      
      <label style="display: block; margin-bottom: 5px; font-weight: bold;">Subject Image:</label>
      <div style="margin-bottom: 15px;">
        <input type="file" name="subject_image" id="subjectImage" accept="image/*" onchange="previewImage(event)" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        <small style="color: #666; display: block; margin-top: 5px;">Accepted formats: JPG, PNG, GIF (Max 5MB)</small>
      </div>
      
      <!-- Image Preview -->
      <div id="imagePreview" style="display: none; margin-bottom: 15px; text-align: center;">
        <img id="previewImg" src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 4px; border: 1px solid #ddd;">
      </div>
      
      <div style="text-align: right;">
        <button type="button" onclick="closeAddSubjectModal()" style="background-color: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; margin-right: 10px; cursor: pointer;">Cancel</button>
        <button type="submit" style="background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Create Subject</button>
      </div>
    </form>
  </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    
    <script>
// Store subjects data for search
const subjectsData = <?php echo json_encode(isLoggedIn() ? getSubjects(getCurrentUserId()) : []); ?>;

function searchSubjects(query) {
  const suggestionsDiv = document.getElementById('searchSuggestions');
  
  if (!query.trim()) {
    suggestionsDiv.style.display = 'none';
    return;
  }
  
  const lowerQuery = query.toLowerCase();
  const matches = subjectsData.filter(subject =>subject.display_name.toLowerCase().includes(lowerQuery)
  );
  
  if (matches.length === 0) {
    suggestionsDiv.innerHTML = '<div class="no-results">Nothing found</div>';
    suggestionsDiv.style.display = 'block';
  } else {
    suggestionsDiv.innerHTML = matches.map(subject => 
      `<div class="suggestion-item" onclick="scrollToSubject(${subject.id})">
        <strong>${subject.display_name}</strong>
        <div style="font-size: 0.85em; color: #6c757d;">${subject.description}</div>
      </div>`
    ).join('');
    suggestionsDiv.style.display = 'block';
  }
}

function handleSearch(event) {
  event.preventDefault();

  const query = document.getElementById('searchInput').value.trim();
  const suggestionsDiv = document.getElementById('searchSuggestions');

  if (!query) {
    suggestionsDiv.style.display = 'none';
    return false;
  }

  const lowerQuery = query.toLowerCase();
  const match = subjectsData.find(subject =>
    subject.display_name.toLowerCase().includes(lowerQuery)
  );

  if (match) {
    suggestionsDiv.style.display = 'none';
    scrollToSubject(match.id);
  } else {
    // SHOW "No results" instead of alert
    suggestionsDiv.innerHTML = `
      <div class="no-results">No results</div>
    `;
    suggestionsDiv.style.display = 'block';
  }

  return false;
}


function scrollToSubject(subjectId) {
  const subjectCard = document.getElementById('subject-' + subjectId);
  if (subjectCard) {
    document.getElementById('searchSuggestions').style.display = 'none';
    document.getElementById('searchInput').value = '';
    
    subjectCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    subjectCard.classList.add('subject-highlight');
    
    setTimeout(() => {
      subjectCard.classList.remove('subject-highlight');
    }, 600);
  }
}

document.addEventListener('click', function(event) {
  const searchInput = document.getElementById('searchInput');
  const suggestionsDiv = document.getElementById('searchSuggestions');
  
  if (!searchInput.contains(event.target) && !suggestionsDiv.contains(event.target)) {
    suggestionsDiv.style.display = 'none';
  }
});

document.getElementById('searchInput').addEventListener('keydown', function(event) {
  if (event.key === 'Enter') {
    event.preventDefault();
    handleSearch(event);
  }
});

function showAddSubjectModal() {
  <?php if (!isLoggedIn()): ?>
    alert('Please login first to add subjects.');
    return;
  <?php endif; ?>
  
  const modal = document.getElementById('addSubjectModal');
  const form = document.getElementById('addSubjectForm');
  const input = document.getElementById('displayName');
  const charCounter = document.getElementById('subjectNameCharCounter');
  
  // Reset form and counter
  form.reset();
  charCounter.textContent = '0/30';
  charCounter.style.color = '#666';
  charCounter.style.fontWeight = 'normal';
  
  // Hide image preview
  document.getElementById('imagePreview').style.display = 'none';
  
  // Character counter listener
  input.oninput = function() {
    const length = input.value.length;
    charCounter.textContent = `${length}/30`;
    
    if (length >= 30) {
      charCounter.style.color = '#dc2626';
      charCounter.style.fontWeight = 'bold';
    } else if (length >= 25) {
      charCounter.style.color = '#f59e0b';
    } else {
      charCounter.style.color = '#666';
      charCounter.style.fontWeight = 'normal';
    }
  };
  
  // Show modal
  modal.style.display = 'block';
  input.focus();
}

function closeAddSubjectModal() {
  document.getElementById('addSubjectModal').style.display = 'none';
  document.getElementById('addSubjectForm').reset();
  document.getElementById('imagePreview').style.display = 'none';
}

function previewImage(event) {
  const file = event.target.files[0];
  if (file) {
    if (file.size > 5 * 1024 * 1024) {
      alert('File size must be less than 5MB');
      event.target.value = '';
      document.getElementById('imagePreview').style.display = 'none';
      return;
    }
    
    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!validTypes.includes(file.type)) {
      showNotification('Please select a valid image file (JPG, PNG, or GIF)', 'error');
      event.target.value = '';
      document.getElementById('imagePreview').style.display = 'none';
      return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('previewImg').src = e.target.result;
      document.getElementById('imagePreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
  } else {
    document.getElementById('imagePreview').style.display = 'none';
  }
}

function deleteSubjectCard(subjectId) {
  const modal = document.getElementById('confirmModal');
  const message = document.getElementById('confirmMessage');
  const yesBtn = document.getElementById('confirmYes');
  const cancelBtn = document.getElementById('confirmCancel');

  // Set the message
  message.textContent = 'Are you sure you want to delete this subject? This will remove all associated data.';

  // Show modal
  modal.classList.remove('hidden');

  // Remove any previous click handlers to prevent duplicates
  yesBtn.onclick = null;
  cancelBtn.onclick = null;

  // Yes button
  yesBtn.onclick = function() {
    modal.classList.add('hidden');

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="delete_subject" value="' + subjectId + '">';
    document.body.appendChild(form);
    form.submit();
  };

  // Cancel button
  cancelBtn.onclick = function() {
    modal.classList.add('hidden');
  };
}


function showNotification(message, type = "info", duration = 3000) {
  const notify = document.getElementById("notify");
  const notifyMessage = document.getElementById("notifyMessage");

  notify.className = `notify ${type}`;
  notifyMessage.textContent = message;

  // Show
  notify.classList.remove("hidden");

  // Auto hide
  setTimeout(() => {
    notify.classList.add("hidden");
  }, duration);
}

function setupPasswordToggle(passwordInputId, toggleBtnId) {
  const passwordInput = document.getElementById(passwordInputId);
  const toggleBtn = document.getElementById(toggleBtnId);
  const icon = toggleBtn.querySelector('i');

  if (!passwordInput || !toggleBtn || !icon) return;

  toggleBtn.addEventListener('click', () => {
    if (passwordInput.type === 'password') {
      passwordInput.type = 'text';
      icon.classList.remove('bi-eye');
      icon.classList.add('bi-eye-slash');
    } else {
      passwordInput.type = 'password';
      icon.classList.remove('bi-eye-slash');
      icon.classList.add('bi-eye');
    }
  });
}

// Initialize for this password field
setupPasswordToggle('registerPass', 'toggleRegisterPassword');
setupPasswordToggle('loginPass', 'togglePassword');

    </script>
 
<?php if (!empty($loginError)) : ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const loginModal = new bootstrap.Modal(
      document.getElementById('loginEmailModal')
    );
    loginModal.show();
  });
</script>
<?php endif; ?>

</body>
</html>