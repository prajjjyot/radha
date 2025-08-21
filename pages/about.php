<?php
session_start();
include '../config/functions.php';

// Initialize DB and connection
$db = new db_functions();
$conn = $db->get_conn();

// Avatar
$avatarFile = $_SESSION['avatar'] ?? "avatar1.png";

// --- Login / Signup variables ---
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

// --- Signup handling ---
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

    if (empty($signup_email_error) && empty($signup_password_error) && empty($signup_confirm_password_error)) {
        if ($db->insert_user($email, $password)) {
            $stmt = $db->get_conn()->prepare("SELECT id FROM qgen_registration WHERE email_id = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();

            $_SESSION['logged_in_email'] = $email;
            $_SESSION['avatar'] = 'avatar1.png';
            $_SESSION['user_id'] = (int)$user['id'];

            header("Location: profile.php");
            exit;
        } else {
            $signup_email_error = "⚠ Email already exists!";
        }
    }
}

// --- Login handling ---
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

    if (empty($login_email_error) && empty($login_password_error)) {
        $user = $db->login_user($email, $password);
        if ($user) {
            $_SESSION['logged_in_email'] = $email;
            $_SESSION['avatar'] = 'avatar1.png';
            $_SESSION['user_id'] = (int)$user['id'];

            header("Location: profile.php");
            exit;
        } else {
            $login_general_error = "Invalid email or password.";
        }
    }
}

// --- Contact form handling ---
$responseMessage = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["contact_submit"])) {

    $name = htmlspecialchars(trim($_POST["name"] ?? ''));
    $email_contact = htmlspecialchars(trim($_POST["email"] ?? ''));
    $message = htmlspecialchars(trim($_POST["message"] ?? ''));

    if (empty($name) || empty($email_contact) || empty($message)) {
        $responseMessage = '<div class="alert alert-danger">All fields are required.</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email_contact, $message);

        if ($stmt->execute()) {
            $responseMessage = '<div class="alert alert-success">Thank you <strong>' . $name . '</strong>! Your message has been received.</div>';
        } else {
            $responseMessage = '<div class="alert alert-danger">Something went wrong. Please try again later.</div>';
        }

        $stmt->close();
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
              <a class="nav-link " href="features.php">Features</a>
            </li>
            <li class="nav-item">
              <a class="nav-link"  href="tutorials.php">Tutorials</a>
            </li>


            <li class="nav-item">
              <a class="nav-link active" aria-current="page" href="about.php">About</a>

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
          <a class="nav-link" href="features.php">Features</a>
        </li>
        <li class="nav-item">
          <a class="nav-link " href="tutorials.php">Tutorial</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" aria-current="page"  href="about.php">About</a>
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
  <!-- About Section -->
<main class="flex-grow-1" style="margin-top: 90px;">
  <section class="container">
    <div class="text-center mb-4">
      <h2 class="fw-bold">About QGen</h2>
      <p class="text-muted">We make backend development faster by auto-generating SQL queries and PHP functions — no manual coding required.</p>
    </div>

    <div class="row g-4">
      <div class="col-md-6 text-center">
        <h4>Our Mission</h4>
        <p>To empower developers, students, and teams by eliminating repetitive SQL and PHP coding, enabling them to focus on building features instead of boilerplate.</p>
      </div>
      <div class="col-md-6 text-center">
        <h4>Our Goal</h4>
        <p>To provide an easy-to-use platform that generates 25+ database operations — CRUD, search, sort, filters, joins, aggregation — with clean, ready-to-use PHP code.</p>
      </div>
    </div>

    <div class="text-center">
      <h4>About the Creator</h4>
      <p>
        QGen was created by <strong>Prajyot Chandrapatle</strong>, a developer passionate about simplifying database integration.
        The tool combines automation, clean code design, and real-world SQL expertise to help both beginners and experienced developers build faster and smarter.
      </p>
    </div>
  </section>

  <!-- Contact Us Section -->
  <section class="container mb-5">
    <div class="text-center mb-4">
      <h2>Contact Us</h2>
      <p class="text-muted">Have questions, suggestions, or feature requests? We're here to help you make the most of QGen.</p>
    </div>

    <?php if (!empty($responseMessage)) echo $responseMessage; ?>

    <!-- Inner styled container for the form -->
    <div class="bg-light p-4 rounded shadow">
      <form class="row g-3" method="POST" action="">
        <div class="col-md-6">
          <label for="inputName" class="form-label">Full Name</label>
          <input type="text" class="form-control" id="inputName" name="name" placeholder="Your Name" required />
        </div>
        <div class="col-md-6">
          <label for="inputEmail" class="form-label">Email Address</label>
          <input type="email" class="form-control" id="inputEmail" name="email" placeholder="you@example.com" required />
        </div>
        <div class="col-12">
          <label for="inputMessage" class="form-label">Message</label>
          <textarea class="form-control" id="inputMessage" name="message" rows="4" placeholder="Write your message here..." required></textarea>
        </div>
        <div class="col-12 text-center">
          <button type="submit" name="contact_submit" class="btn btn-primary px-4">Send Message</button>
        </div>
      </form>
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
