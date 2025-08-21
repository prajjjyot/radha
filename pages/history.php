<?php
session_start();
include("../config/functions.php");

$db = new db_functions();
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

// ----------------------
// Signup Handling
// ----------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_btn"])) {
    $email = trim($_POST["email_id"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $show_signup_modal = true;

    if (empty($email)) $signup_email_error = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $signup_email_error = "Invalid email format.";
    if (empty($password)) $signup_password_error = "Password is required.";
    if ($password !== $confirm_password) $signup_confirm_password_error = "Passwords do not match.";

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

            header("Location: pages/profile.php");
            exit;
        } else {
            $signup_email_error = "⚠ Email already exists!";
        }
    }
}

// ----------------------
// Login Handling
// ----------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login_btn"])) {
    $email = trim($_POST["email_id"]);
    $password = trim($_POST["password"]);
    $show_login_modal = true;

    if (empty($email)) $login_email_error = "Email is required.";
    if (empty($password)) $login_password_error = "Password is required.";

    if (empty($login_email_error) && empty($login_password_error)) {
        $user = $db->login_user($email, $password);
        if ($user) {
            $_SESSION['logged_in_email'] = $email;
            $_SESSION['avatar'] = 'avatar1.png';
            $_SESSION['user_id'] = (int)$user['id'];

            header("Location: pages/profile.php");
            exit();
        } else {
            $login_general_error = "Invalid email or password.";
        }
    }
}

// ----------------------
// User History Handling
// ----------------------
// Only if user is logged in
if (!empty($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];

    // Handle delete request
    if (isset($_GET['delete_id'])) {
        $delete_id = (int)$_GET['delete_id'];
        $db->delete_history($delete_id, $user_id);
        header("Location: history.php");
        exit;
    }

    // Fetch user history
    $history = $db->get_user_history($user_id);
} else {
    // If not logged in, redirect to home/login
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QGen - History</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
  <link rel="stylesheet" type="text/css" href="../aseets\css\style.css" />

  <style>
    /* Inline styles moved from the body for better organization */
    .plain-toggle-btn {
        background: none;
        border: none;
        padding: 0;
        margin: 0;
        color: #0d6efd;
        font-size: 14px;
        cursor: pointer;
        text-decoration: underline;
    }

    .small-delete-btn {
        background-color: #dc3545;
        color: #fff;
        border: none;
        padding: 2px 6px;
        font-size: 12px;
        border-radius: 3px;
        text-decoration: none;
        display: inline-block;
        cursor: pointer;
    }

    .small-delete-btn:hover {
        background-color: #c82333;
        color: #fff;
    }

    /* Key change for responsiveness */
    .table-responsive-container {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
    }
  </style>
</head>

<body>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q"
    crossorigin="anonymous"></script>

  <!--Navbar-->
  <nav class="navbar navbar-expand-lg navbar-light bg-light shadow fixed-top w-100">
    <div class="container-fluid">
        <a href="../index.php">
            <img src="../aseets\img\sql-server-icon-png-29.png" alt="QGen Logo" width="25">
        </a>
        <a class="navbar-brand me-auto" href="../index.php">QGen</a>

        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasNavbarLabel">QGen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <ul class="navbar-nav justify-content-center flex-grow-1 pe-3">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="features.php">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="tutorials.php">Tutorials</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <?php if (isset($_SESSION['logged_in_email'])): ?>
                        <li class="nav-item"><a class="nav-link active" aria-current="page" href="history.php">History</a></li>
                    <?php endif; ?>
                </ul>
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

        <div class="d-none d-lg-flex gap-2 align-items-center">
            <?php if (isset($_SESSION['logged_in_email'])): ?>
                <a href="profile.php">
                    <img src="<?php echo htmlspecialchars('../assets/img/' . $avatarFile); ?>" alt="User Avatar" style="width:40px; height:40px; border-radius:50%; object-fit: cover;">
                </a>
            <?php else: ?>
                <a href="#" class="login-button" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a>
                <a href="#" class="login-button" style="font-size: 14px;" data-bs-toggle="modal" data-bs-target="#signupModal">Sign up &#x25B8;</a>
            <?php endif; ?>
        </div>

        <button class="navbar-toggler pe-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
  </nav>
  <!--End Navbar-->

  <main class="flex-grow-1" style="margin-top: 90px;">
    <section class="container">
      <div class="text-center mb-4">
        <h2 class="fw-bold">Your Query History</h2>
        <p class="text-muted">Quickly access all the SQL queries you've generated with QGen — review, reuse, or refine them anytime without starting over.</p>
      </div>
    </section>
  </main>

  <div class="container mt-5">
    <?php if (empty($history)): ?>
        <p class="text-center">No history found.</p>
    <?php else: ?>
        <!-- This new div makes the table scrollable on small screens -->
        <div class="table-responsive-container">
            <table class="table table-bordered table-sm" style="font-size: 12px; min-width: 800px;"> <!-- min-width ensures table doesn't get too squished -->
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Generator</th>
                        <th>Task</th>
                        <th>Table</th>
                        <th>Columns</th>
                        <th>Query Line</th>
                        <th>Functions</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $idx => $row): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><?= htmlspecialchars($row['generator']) ?></td>
                            <td><?= htmlspecialchars($row['task_name']) ?></td>
                            <td><?= htmlspecialchars($row['table_name']) ?></td>
                            <td><?= htmlspecialchars($row['columns_text']) ?></td>
                            <td>
                                <pre id="query-<?= $row['id'] ?>" style="max-height:50px; overflow:hidden; white-space:pre-wrap; margin-bottom: 5px;"><?= htmlspecialchars($row['query_line']) ?></pre>
                                <button type="button" onclick="toggleFunction('query-<?= $row['id'] ?>', this)" class="plain-toggle-btn">View More ▼</button>
                            </td>
                            <td>
                                <pre id="func-<?= $row['id'] ?>" style="max-height:50px; overflow:hidden; white-space:pre-wrap; margin-bottom: 5px;"><?= htmlspecialchars($row['generated_functions']) ?></pre>
                                <button type="button" onclick="toggleFunction('func-<?= $row['id'] ?>', this)" class="plain-toggle-btn">View More ▼</button>
                            </td>
                            <td><?= $row['created_at'] ?></td>
                            <td>
                                <a href="?delete_id=<?= $row['id'] ?>" class="small-delete-btn" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
  </div>

  <script>
    function toggleFunction(id, btn) {
        const el = document.getElementById(id);
        if (el.style.maxHeight === 'none') {
            el.style.maxHeight = '50px';
            btn.innerHTML = 'View More ▼';
        } else {
            el.style.maxHeight = 'none';
            btn.innerHTML = 'View Less ▲';
        }
    }
  </script>

  <!-- Footer -->
  <footer style="background-color: #343a40; color: #fff; padding: 30px 0; text-align: center; width: 100%; margin-top: 50px;">
    <div class="container-fluid px-0">
        <div class="row justify-content-center text-center text-md-start" style="margin: 0; padding: 0 20px;">
            <div class="col-md-4 mb-3 d-flex flex-column align-items-center align-items-md-start">
                <img src="../aseets\img\sql-server-icon-png-29.png" alt="QGen Logo" width="50" style="margin-bottom: 10px;">
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

</body>
</html>