<?php
include("../config/functions.php");
session_start();


$avatarFile = $_SESSION['avatar'] ?? "avatar1.png";


$db = new db_functions();

$signup_email_error = "";
$signup_password_error = "";
$signup_confirm_password_error = "";
$signup_general_message = "";
$login_email_error = "";
$login_password_error = "";
$login_general_error = "";
$login_success = "";

$email = "";
$password = "";
$confirm_password = "";

$show_signup_modal = false;
$show_login_modal = false;

// Signup
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_btn"])) {
    $email = trim($_POST["email_id"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $show_signup_modal = true;

    if (empty($email)) {
        $signup_email_error = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $signup_email_error = "Invalid email format.";
    }

    if (empty($password)) {
        $signup_password_error = "Password is required.";
    }

    if ($password !== $confirm_password) {
        $signup_confirm_password_error = "Passwords do not match.";
    }



if ($db->insert_user($email, $password)) {
    // Get the newly inserted user id
    $stmt = $db->get_conn()->prepare("SELECT id FROM qgen_registration WHERE email_id = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    
    $_SESSION['logged_in_email'] = $email;
    $_SESSION['avatar'] = 'avatar1.png';
    
    // Add this line for history
    $_SESSION['user_id'] = (int)$user['id'];

    header("Location: profile.php");
    exit;
} else {
    $signup_email_error = "⚠ Email already exists!";
}





}


// Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login_btn"])) {
    $email = trim($_POST["email_id"]);
    $password = trim($_POST["password"]);

    $show_login_modal = true;

    if (empty($email)) {
        $login_email_error = "Email is required.";
    }

    if (empty($password)) {
        $login_password_error = "Password is required.";
    }

  if ($db->login_user($email, $password)) {
    $user = $db->login_user($email, $password); // get full user row
    $login_success = "Login successful!";
    $_SESSION['logged_in_email'] = $email;
    $_SESSION['avatar'] = 'avatar1.png';
    
    // ✅ Add this line for history
    $_SESSION['user_id'] = (int)$user['id'];

    header("Location: profile.php");
    exit();
} else {
    $login_general_error = "Invalid email or password.";
}

    
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QGen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">


  <link rel="stylesheet" type="text/css" href="../aseets\css\style.css" />


</head>

<body>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q"
    crossorigin="anonymous"></script>


  <!--Navbar-->
  <nav class="navbar navbar-expand-lg navbar-light bg-light shadow  fixed-top w-100">
    <div class="container-fluid">
          <a href="../index.php">
  <img src="../aseets\img\sql-server-icon-png-29.png" alt="Bootstrap" width="25">
</a>
      <a class="navbar-brand me-auto " href="../index.php">QGen</a>

      <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
        <div class="offcanvas-header">
          <h5 class="offcanvas-title" id="offcanvasNavbarLabel">QGen</h5>

          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
          <ul class="navbar-nav justify-content-center flex-grow-1 pe-3">
            <li class="nav-item">
              <a class="nav-link" href="../index.php">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link active" aria-current="page" href="features.php">Features</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="tutorials.php">Tutorials</a>
            </li>


            <li class="nav-item">
              <a class="nav-link" href="about.php">About</a>

            <li class="nav-item">

<?php if (isset($_SESSION['logged_in_email'])): ?>
<li class="nav-item">
  <a class="nav-link" href="history.php">History</a>
</li>
<?php endif; ?>

          </ul>
          <!-- Show Login/Sign Up only on small screens -->

          <div class="d-lg-none mt-3">
  <?php if (isset($_SESSION['logged_in_email'])): ?>
    <a href="profile.php" class="btn btn-outline-secondary w-100 mb-2">My Profile</a>
  <?php else: ?>
    <a href="#" class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="offcanvas">Login</a>
    <a href="#" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#signupModal" data-bs-dismiss="offcanvas">Sign Up</a>
  <?php endif; ?>
</div>

        </div>
      </div>
      <!-- Show only on large screens -->
      <div class="d-none d-lg-flex gap-2">
  <?php if (isset($_SESSION['logged_in_email'])): ?>
    <!-- Profile Icon -->
     
   <div class="d-flex align-items-center">
  <?php if (!empty($_SESSION['name'])): ?>
    <span style="font-family: 'Comic Sans MS', cursive; font-size: 16px; margin-right: 8px;">
      <?= htmlspecialchars($_SESSION['name']) ?>
    </span>
  <?php endif; ?>
  <a class="nav-link" href="profile.php">
    <img src="../aseets/img/<?= $_SESSION['avatar'] ?? 'avatar1.jpg' ?>" alt="Profile"
         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
  </a>
</div>

  <?php else: ?>
    <!-- Login / Signup Buttons -->
    <a href="#" class="login-button" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a>
    <a href="#" class="login-button" style="font-size: 14px;" data-bs-toggle="modal" data-bs-target="#signupModal">Sign up &#x25B8;</a>
  <?php endif; ?>
</div>


      <!-- Home Page Offcanvas Toggler -->
      <button class="navbar-toggler pe-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar"
        aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

    </div>
  </nav>

  <!-- Offcanvas Menu (Right Side) -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="offcanvasNavbarLabel">QGen Menu</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <ul class="navbar-nav justify-content-end flex-grow-1 pe-3">
        <li class="nav-item">
          <a class="nav-link" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" aria-current="page" href="features.php">Features</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="tutorials.php">Tutorial</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="about.php">About</a>
        </li>
        <li class="nav-item">
  <a class="nav-link" href="pages/about.php">About</a>
</li>

<?php if (isset($_SESSION['logged_in_email'])): ?>
<li class="nav-item">
  <a class="nav-link" href="history.php">History</a>
</li>
<?php endif; ?>


        
      </ul>
    </div>
  </div>

  <!--End Navbar-->


  <!-- Feature Section -->
<main class="flex-grow-1" style="margin-top: 90px;">
  <section class="container">
    <div class="text-center mb-4">
      <h2 class="fw-bold">Our Features</h2>
      <p class="text-muted">Discover why QGen is the ultimate PHP and SQL code generator.</p>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Instant Code Generation</h5>
            <p class="card-text">Enter your table name, columns, and task — QGen builds SQL queries and fully formatted PHP functions instantly.</p>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Supports 25+ Operations</h5>
            <p class="card-text">Generate CRUD, search, sort, filters, joins, aggregation, pagination, and more — all with one click.</p>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Ready-to-Use PHP Functions</h5>
            <p class="card-text">Each query is wrapped in clean PHP code with error handling, making integration into your project effortless.</p>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Customizable Output</h5>
            <p class="card-text">Edit and adjust the generated SQL or PHP to fit your specific requirements without starting from scratch.</p>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Query History & Reuse</h5>
            <p class="card-text">Your generated code is saved after login so you can revisit, reuse, or modify it anytime.</p>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Fast & Error-Free</h5>
            <p class="card-text">Avoid manual coding mistakes and save hours — QGen ensures accurate, production-ready SQL and PHP output every time.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

  <!-- Footer -->
<footer style="background-color: #343a40; color: #fff; padding: 30px 0; text-align: center; width: 100%;">
    <div class="container-fluid px-0">
      <div class="row justify-content-center text-center text-md-start" style="margin: 0; padding: 0 20px;">
        <div class="col-md-4 mb-3 d-flex flex-column align-items-center align-items-md-start">
          <img src="../aseets/img/sql-server-icon-png-29.png" alt="QGen Logo" width="50" style="margin-bottom: 10px;">
          <h5>QGen</h5>
          <p style="font-size: 14px;">Effortless SQL Generation for Everyone</p>
        </div>
        <div class="col-md-4 mb-3">
          <h6>Quick Links</h6>
          <ul class="list-unstyled" style="font-size: 14px;">
            <li><a href="../index.php" style="color: #bbb; text-decoration: none;">Home</a></li>
            <li><a href="features.php" style="color: #bbb; text-decoration: none;">Features</a></li>
            <li><a href="about.php" style="color: #bbb; text-decoration: none;">About</a></li>
          </ul>
        </div>
        <div class="col-md-4 mb-3">
          <h6>Contact</h6>
          <p style="font-size: 14px;">Email: support@qgen.com</p>
          <p style="font-size: 14px;">Phone: +91-123-456-7890</p>
        </div>
      </div>
      <hr style="border-color: #555; margin: 20px auto; width: 90%;">
      <p style="margin: 0; font-size: 13px;">&copy; 2025 QGen. All rights reserved.</p>
    </div>
  </footer>

  <script src="../aseets/js/bootstrap.bundle.js"></script>
</body>
</html>
