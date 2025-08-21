<?php
include("config/functions.php");
session_start();

// =========== PATH CORRECTION: DEFINE BASE URL ===========
// This automatically creates the correct base path for all assets (CSS, JS, images)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
// This handles projects in subfolders (e.g., http://localhost/myproject/)
$script_name = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
define('BASE_URL', $protocol . $host . $script_name);
// ========================================================


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

    // Use BASE_URL for robust redirect
    header("Location: " . BASE_URL . "pages/profile.php");
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

    // Use BASE_URL for robust redirect
    header("Location: " . BASE_URL . "pages/profile.php");
    exit();
} else {
    $login_general_error = "Invalid email or password.";
}

    
}
?>




<?php
$generatedSQL = "";
$generatedFunctions = "";

if (isset($_POST['generate_btn'])) {
    $table = trim($_POST['table_name']);
    $columns = trim($_POST['columns']);
    $columnsArr = array_filter(array_map('trim', explode(",", $columns)));
    $firstCol = $columnsArr[0] ?? 'id';
    $task = $_POST['task_name'] ?? null;

    // --- INSERT ---
    $insertFunc = "insert_{$table}_data";
    $vars = array_map(fn($c) => "\$var_$c", $columnsArr);
    $funcArgsInsert = implode(", ", $vars);
    $bindsInsert = str_repeat("s", count($columnsArr));
    $bindParamsInsert = implode(", ", $vars);
    $placeholders = implode(", ", array_fill(0, count($columnsArr), "?"));
    $columnStr = implode("`, `", $columnsArr);
    $insertSQL = "INSERT INTO `$table` (`$columnStr`) VALUES ($placeholders);";
    $insertCode = <<<PHP
function $insertFunc($funcArgsInsert)
{
    if (\$stmt = \$this->con->prepare("INSERT INTO `$table` (`$columnStr`) VALUES ($placeholders)")) {
        \$stmt->bind_param("$bindsInsert", $bindParamsInsert);
        return \$stmt->execute();
    }
    return false;
}
PHP;

    // --- SELECT ---
    $selectFunc = "get_{$table}_data";
    $selectSQL = "SELECT `$columnStr` FROM `$table`;";
    $resVars = array_map(fn($c) => "\$res_$c", $columnsArr);
    $resVarsStr = implode(", ", $resVars);
    $rowSet = "";
    foreach ($columnsArr as $col) {
        $rowSet .= "        \$data[\$row_no]['$col'] = \$res_$col;\n";
    }
    $selectCode = <<<PHP
function $selectFunc()
{
    if (\$stmt = \$this->con->prepare("SELECT `$columnStr` FROM `$table`")) {
        \$stmt->execute();
        \$stmt->bind_result($resVarsStr);
        \$data = [];
        \$row_no = 0;
        while (\$stmt->fetch()) {
$rowSet        \$row_no++;
        }
        return \$data;
    }
    return false;
}
PHP;

    // --- UPDATE ---
    $updateFunc = "update_{$table}_by_id";
    $varsUpdate = array_map(fn($c) => "\$var_$c", $columnsArr);
    $funcArgsUpdate = implode(", ", array_merge($varsUpdate, ['\$id']));
    $bindsUpdate = str_repeat("s", count($columnsArr)) . "s";
    $bindParamsUpdate = implode(", ", array_merge($varsUpdate, ['\$id']));
    $setCols = implode(", ", array_map(fn($c) => "`$c` = ?", $columnsArr));
    $updateSQL = "UPDATE `$table` SET $setCols WHERE `$firstCol` = ?;";
    $updateCode = <<<PHP
function $updateFunc($funcArgsUpdate)
{
    if (\$stmt = \$this->con->prepare("UPDATE `$table` SET $setCols WHERE `$firstCol` = ?")) {
        \$stmt->bind_param("$bindsUpdate", $bindParamsUpdate);
        return \$stmt->execute();
    }
    return false;
}
PHP;

    // --- DELETE ---
    $deleteFunc = "delete_{$table}_by_id";
    $deleteSQL = "DELETE FROM `$table` WHERE `$firstCol` = ?;";
    $deleteCode = <<<PHP
function $deleteFunc(\$id)
{
    if (\$stmt = \$this->con->prepare("DELETE FROM `$table` WHERE `$firstCol` = ?")) {
        \$stmt->bind_param("s", \$id);
        return \$stmt->execute();
    }
    return false;
}
PHP;

    // --- Combine SQL and Functions ---
    $generatedSQL = "-- INSERT QUERY --\n$insertSQL\n\n-- SELECT QUERY --\n$selectSQL\n\n-- UPDATE QUERY --\n$updateSQL\n\n-- DELETE QUERY --\n$deleteSQL";
    $generatedFunctions = "$insertCode\n\n$selectCode\n\n$updateCode\n\n$deleteCode";

    // --- Save History for Logged-in User ---
    if (!isset($sqlQueryLine)) {
        $sqlQueryLine = $generatedSQL ?? "";
    }


   if (!empty($_SESSION['user_id']) && isset($_POST['generate_btn'])) {
    $db->save_history(
        (int)$_SESSION['user_id'],
        'basic',
        $task ?? null,
        $table,
        $columns,
        $sqlQueryLine ?? null,
        $generatedSQL,
        $generatedFunctions
    );
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

  <!-- Corrected CSS Path -->
  <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>aseets\css\style.css" />

  <!-- CSS FIX for layout -->
  <style>
    body {
      align-items: initial !important; 
    }
    .main-content {
      flex-grow: 1;
    }
    .hero-section .container {
      height: auto !important;
    }
  </style>

</head>

<body>
  
  <!--Navbar-->
  <nav class="navbar navbar-expand-lg navbar-light bg-light shadow  fixed-top w-100">
    <div class="container-fluid">
          <a href="../index.php">
  <img src="aseets\img\sql-server-icon-png-29.png" alt="Bootstrap" width="25">
</a>
      <a class="navbar-brand me-auto " href="index.php">QGen</a>

      <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
        <div class="offcanvas-header">
          <h5 class="offcanvas-title" id="offcanvasNavbarLabel">QGen</h5>

          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
          <ul class="navbar-nav justify-content-center flex-grow-1 pe-3">
            <li class="nav-item">
              <a class="nav-link active" aria-current="page" href="index.php">Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link " href="pages\features.php">Features</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="pages\tutorials.php">Tutorials</a>
            </li>


            <li class="nav-item">
              <a class="nav-link" href="pages\about.php">About</a>

            <li class="nav-item">

<?php if (isset($_SESSION['logged_in_email'])): ?>
<li class="nav-item">
  <a class="nav-link" href="pages\history.php">History</a>
</li>
<?php endif; ?>

          </ul>
          <!-- Show Login/Sign Up only on small screens -->

          <div class="d-lg-none mt-3">
  <?php if (isset($_SESSION['logged_in_email'])): ?>
    <a href="pages\profile.php" class="btn btn-outline-secondary w-100 mb-2">My Profile</a>
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
  <a class="nav-link" href="pages\profile.php">
    <img src="aseets/img/<?= $_SESSION['avatar'] ?? 'avatar1.jpg' ?>" alt="Profile"
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
          <a class="nav-link active" aria-current="page" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link " href="pages\features.php">Features</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="pages\tutorials.php">Tutorial</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="pages\about.php">About</a>
        </li>
        <li class="nav-item">
  <a class="nav-link" href="pages/about.php">About</a>
</li>

<?php if (isset($_SESSION['logged_in_email'])): ?>
<li class="nav-item">
  <a class="nav-link" href="pages\history.php">History</a>
</li>
<?php endif; ?>


        
      </ul>
    </div>
  </div>

  <!--End Navbar-->


  <main class="main-content">
    <!--Hero Section-->
    <section class="hero-section">
      <div class="container">
        <div class="d-flex align-items-center justify-content-center flex-column"
          style="padding-top: 100px; text-align: center;">
          <h1 style="font-size: 2.5rem; text-align: center; font-weight: bold; color:black;">
            Write Less, Build More — Auto-Generate 
            <span style="
              background: linear-gradient(270deg, #ff6a00, #ee0979, #007bff, #00c6ff);
              background-size: 600% 600%;
              -webkit-background-clip: text;
              -webkit-text-fill-color: transparent;
              background-clip: text;
              color: transparent;
              animation: gradientMove 6s ease infinite;
              display: inline-block;
            ">
              Queries &amp; Functions
            </span>
          </h1>
          <style>
          @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
          }
          </style>
          <h2 style="color:black;">Your database logic, delivered in seconds.</h2>
        </div>

        <div class="form-blocker" style="position: relative;">
          

          <form method="POST" action="">
            <div class="form-container">



            <?php if (empty($_SESSION['user_id'])): ?>
            <div onclick="showSignupModal()" 
                 style="
                    position: absolute; 
                    top: 0; 
                    left: 0; 
                    width: 100%; 
                    height: 100%; 
                    background: rgba(255,255,255,0.0);
                    z-index: 10; 
                    cursor: pointer;">
            </div>
          <?php endif; ?>

              <div class="input-group" style="display: flex; flex-direction: column; gap: 10px;">
                <!-- =========== LABEL FIX STARTS HERE =========== -->
                <label for="table_name" style="text-align: center; display: block; color: #000000ff;">Enter table name:</label>
                <input type="text" name="table_name" placeholder="Enter table name" style="padding: 10px;" required value="<?php echo htmlspecialchars($_POST['table_name'] ?? ''); ?>">

                
<label for="columns" style="text-align: center; display: block; color: #000000ff;">Enter columns comma-separated:</label>                <input name="columns" placeholder="Enter columns comma-separated" style="padding: 10px; height: 80px;" required value="<?php echo htmlspecialchars($_POST['columns'] ?? ''); ?>">

                <button class="full-width-btn" type="submit" style=" border-radius: 8px;" name="generate_btn">Generate Basic</button>
                

                                <label  style="text-align: center; display: block; color: #000000ff;">Generated SQL:</label>

                <div style="position: relative; margin-top: 10px;">
                  <button onclick="copySQL()" type="button" style="
                    position: absolute; top: 10px; right: 10px; padding: 6px 12px; font-size: 13px;
                    border: none; background-color: #007bff; color: #fff; border-radius: 5px; cursor: pointer;">Copy SQL</button>
                  <pre id="generated-sql" style="
                    background-color: #e6ffe6; padding: 15px; border-radius: 6px; border: 1px solid #ccc;
                    color: #000; font-size: 15px; overflow-x: auto; height: 180px; white-space: pre-wrap; text-align: left;
                  "><?php echo htmlspecialchars($generatedSQL ?? ''); ?></pre>
                </div>


                <label  style="text-align: center; display: block; color: #000000ff;">Generated PHP Function:

</label>

                <div style="position: relative; margin-top: 20px;">
                  <button onclick="copyPHP()" type="button" style="
                    position: absolute; top: 10px; right: 10px; padding: 6px 12px; font-size: 13px;
                    border: none; background-color: green; color: #fff; border-radius: 5px; cursor: pointer;">Copy PHP</button>
                  <pre id="generated-php" style="
                    background-color: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #ccc;
                    color: #000; font-size: 15px; overflow-x: auto; height: 350px; white-space: pre-wrap;text-align: left;
                  "><?php echo htmlspecialchars($generatedFunctions ?? ''); ?></pre>
                </div>
              </div>
              
              <style>
                .full-width-btn { position: relative; display: flex; justify-content: center; align-items: center; padding: 12px 40px; background: #222; color: white; border: none; cursor: pointer; font-size: 18px; border-radius: 8px; overflow: visible; }
                .fire-icon { position: absolute; width: 300px; top: 50%; transform: translateY(-50%); pointer-events: none; }
                .fire-left { left: -20px; transform: translateY(-50%) rotate(90deg); }
                .fire-right { right: -20px; transform: translateY(-50%) rotate(-90deg); }
                .button-text { position: relative; z-index: 2; }
              </style>

              <button class="full-width-btn mt-3" type="button" onclick="window.location.href='pages/advanced_generator.php'">
                <!-- Corrected Image Paths -->
                <img src="<?php echo BASE_URL; ?>aseets\img\source-unscreen.gif" alt="fire left" class="fire-icon fire-left d-none d-md-block">
                <span class="button-text">Go Advanced →</span>
                <img src="<?php echo BASE_URL; ?>aseets\img\source-unscreen.gif" alt="fire right" class="fire-icon fire-right d-none d-md-block">
              </button>
            </div>
          </form>

          <script>
            function copySQL() { navigator.clipboard.writeText(document.getElementById("generated-sql").innerText); alert("SQL queries copied!"); }
            function copyPHP() { navigator.clipboard.writeText(document.getElementById("generated-php").innerText); alert("PHP functions copied!"); }
          </script>
          
          <div class="my-5"></div>
          
          <div class="row justify-content-center g-4">
            <div class="col-lg-4 col-md-6">
              <div class="card h-100 shadow" style="background: linear-gradient(135deg, #baffffff, #ffffffff); border-radius: 10px; border: none; color: black;">
                <div class="card-header text-center" style="background-color: rgba(255,255,255,0.1); border-bottom: 1px solid rgba(255,255,255,0.2);">
                  <span style="font-size: 24px;">Generate SQL Instantly</span>
                </div>
                <div class="card-body text-center">
                  <p class="card-text">Create SQL queries automatically from table & column inputs.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card h-100 shadow" style="background: linear-gradient(135deg, #ffffffff, #ffffffff); border-radius: 10px; border: none; color: black;">
                <div class="card-header text-center" style="background-color: rgba(255,255,255,0.1); border-bottom: 1px solid rgba(255,255,255,0.2);">
                  <span style="font-size: 24px;">Save & Track History</span>
                </div>
                <div class="card-body text-center">
                  <p class="card-text">All generated queries are saved for quick access & reuse.</p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 col-md-6">
              <div class="card h-100 shadow" style="background: linear-gradient(135deg, #ffffffff , #baffffff); border-radius: 10px; border: none; color: black;">
                <div class="card-header text-center" style="background-color: rgba(255,255,255,0.1); border-bottom: 1px solid rgba(255,255,255,0.2);">
                  <span style="font-size: 24px;">Advanced SQL & PHP Generator</span>
                </div>
                <div class="card-body text-center">
                  <p class="card-text">Generate 25+ SQL queries and PHP functions automatically, from CRUD to complex operations.</p>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-4 text-center">
            <p style="font-size: 16px; color: #333;">
             "Databases hold all your important data, but writing SQL can be tricky. Our AI-powered QGen instantly turns your inputs into ready-to-use SQL queries and PHP functions for MySQL, PostgreSQL, MSSQL, and more."
            </p>
          </div>

          <div class="highlight-section mt-4"
            style="background: linear-gradient(135deg, #baffffff, #ffffffff, #baffffff); padding: 25px; border-radius: 10px; text-align: left;">
            <h2 style="font-size: 24px; color: #000; margin-bottom: 20px;">Challenges of Manual SQL Writing</h2>
            <ul style="font-size: 16px; color: #333; line-height: 1.6; padding-left: 20px; margin: 0;">
              <li><strong>Complex Syntax:</strong> SQL can be tricky, especially JOINs or subqueries. QGen simplifies it with plain-language inputs.</li>
              <li><strong>Time-Consuming:</strong> Writing queries manually takes time. QGen generates them instantly.</li>
              <li><strong>Error-Prone:</strong> Mistakes can break your data. QGen produces accurate SQL code to reduce errors.</li>
              <li><strong>Limited Knowledge:</strong> Not familiar with advanced SQL? QGen creates complex queries from simple descriptions.</li>
            </ul>
          </div>
          
          <div style="text-align: center; margin-top: 40px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #333;">HOW IT WORKS</div>
          <div style="text-align: center; font-size: 24px; font-weight: bold; color: #333; margin-bottom: 20px;">Easy Steps to Start SQL Query Generation with AI</div>
          
          <div style="max-width: 700px; margin: 0 auto 40px; background-color: #ffffff; border-radius: 10px; padding: 10px 20px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);">
            <div style="background-color: #f1f1f1; margin-bottom: 15px; padding: 15px 20px; border-radius: 8px; text-align: left;">
              <h3 style="margin-top: 0; font-size: 18px; color: #000;">Step 1 - Sign Up Quickly</h3>
              <p style="margin: 8px 0 0; color: #333; font-size: 16px; line-height: 1.7;">Create an account using Google or manually.</p>
            </div>
            <div style="background-color: #f1f1f1; margin-bottom: 15px; padding: 15px 20px; border-radius: 8px; text-align: left;">
              <h3 style="margin-top: 0; font-size: 18px; color: #000;">Step 2 - Add Database (Optional)</h3>
              <p style="margin: 8px 0 0; color: #333; font-size: 16px; line-height: 1.7;">Provide your schema so AI can generate smarter queries.</p>
            </div>
            <div style="background-color: #f1f1f1; margin-bottom: 15px; padding: 15px 20px; border-radius: 8px; text-align: left;">
              <h3 style="margin-top: 0; font-size: 18px; color: #000;">Step 3 - Generate Queries</h3>
              <p style="margin: 8px 0 0; color: #333; font-size: 16px; line-height: 1.7;">Use AI to create, explain, or refine SQL queries instantly.</p>
            </div>
            <div style="background-color: #f1f1f1; margin-bottom: 0; padding: 15px 20px; border-radius: 8px; text-align: left;">
              <h3 style="margin-top: 0; font-size: 18px; color: #000;">Step 4 - Collaborate & Automate</h3>
              <p style="margin: 8px 0 0; color: #333; font-size: 16px; line-height: 1.7;">Share workflows and automate repetitive SQL tasks.</p>
            </div>
          </div>





           <!-- ================== MISSING SECTIONS RE-ADDED HERE ================== -->
          <!-- Section Title -->
          <div style="text-align: center; margin-top: 50px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #333;">
            Example: Generated SQL & PHP Function
          </div>

          <!-- Example Output Container -->
          <div style="max-width: 800px; margin: 20px auto 50px; background-color: #ffffff; border-radius: 10px; padding: 20px 25px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); text-align: left;">
            <p style="font-size: 16px; color: #333; margin-bottom: 10px;">
              <strong>Task:</strong> Insert<br>
              <strong>Table:</strong> employees<br>
              <strong>Columns:</strong> name, age, salary
            </p>
            <p style="font-size: 16px; color: #333; margin-bottom: 8px;">
              <strong>Generated SQL:</strong>
            </p>
            <div style="position: relative;">
              <pre id="example-sql" style="background-color: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #ccc; color: #000; font-size: 15px; overflow-x: auto; margin: 0;">INSERT INTO employees (name, age, salary) VALUES (?, ?, ?);</pre>
            </div>
            <p style="font-size: 16px; color: #333; margin: 15px 0 8px;">
              <strong>Generated PHP Function:</strong>
            </p>
            <div style="position: relative;">
              <pre id="example-php" style="background-color: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #ccc; color: #000; font-size: 15px; overflow-x: auto; margin: 0;">function insert_employee($name, $age, $salary) {
    global $conn;
    $stmt = $conn->prepare('INSERT INTO employees (name, age, salary) VALUES (?, ?, ?)');
    $stmt->bind_param('sis', $name, $age, $salary);
    $stmt->execute();
    return $stmt->affected_rows;
}</pre>
            </div>
          </div>

          <div class="mt-4 text-center">
            <h2>The Mechanics Behind QGen</h2>
            <p style="font-size: 16px; color: #333; max-width: 800px; margin: 0 auto; line-height: 1.6;">
              QGen takes your inputs like task name, table name, and columns, then automatically generates the SQL query and
              PHP function for it. It supports multiple SQL tasks, from basic CRUD to advanced operations, making database
              operations faster, accurate, and easy to understand.
            </p>
          </div>

          <div class="highlight-section mt-4" style="background: linear-gradient(135deg, #d0e8ff,#f1f8ff ); padding: 25px; border-radius: 10px; text-align: left;">
  <h2 style="font-size: 24px; color: #000; margin-bottom: 20px;">Capabilities and Common Use Cases</h2>
  <ul style="font-size: 16px; color: #333; line-height: 1.6; padding-left: 20px; margin: 0;">
    <li><strong>Instant Query Generation:</strong> Create optimized SQL queries and ready-to-use PHP functions just by entering a table name and columns—no manual coding required.</li>
    <li><strong>CRUD Operations in Seconds:</strong> Generate INSERT, SELECT, UPDATE, and DELETE statements with properly formatted PHP wrappers to integrate directly into your project.</li>
    <li><strong>Advanced Features:</strong> Build search, sort, pagination, and filter queries automatically, avoiding repetitive SQL writing and debugging.</li>
    <li><strong>Error-Free Code Output:</strong> All generated PHP functions include proper syntax, date/time handling, and error display to save hours of manual work.</li>
    <li><strong>History Tracking:</strong> Save and view all previously generated queries after login, making it easy to reuse or modify past work.</li>
  </ul>
</div>


       <div class="highlight-section mt-4" style="background: linear-gradient(135deg, #f1f8ff, #d0e8ff); padding: 25px; border-radius: 10px; text-align: left;">
  <h2 style="font-size: 24px; color: #000; margin-bottom: 20px;">Real-World Applications for Various Professionals</h2>
  <ul style="font-size: 16px; color: #333; line-height: 1.6; padding-left: 20px; margin: 0;">
    <li><strong>Web Developers:</strong> Instantly generate PHP functions and SQL queries for any table, speeding up backend development without writing repetitive code.</li>
    <li><strong>Startup Founders:</strong> Build MVPs faster by creating ready-to-use database operations (CRUD, search, filters) without hiring a full backend team.</li>
    <li><strong>Freelancers:</strong> Deliver projects quickly with automated, error-free code output, reducing debugging time and boosting client satisfaction.</li>
    <li><strong>Students & Learners:</strong> Understand SQL and PHP integration better by seeing auto-generated code samples tailored to real use cases.</li>
    <li><strong>Project Managers:</strong> Review database operations and prototypes quickly without needing deep SQL or PHP expertise.</li>
  </ul>
</div>

          <!-- ================== END OF RE-ADDED SECTIONS ================== -->

         <!-- FAQ Section -->
<div style="text-align: center; margin-top: 50px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #333;">
  Frequently Asked Questions
</div>
<div class="accordion mt-4" id="faqAccordion" style="max-width: 800px; margin: 20px auto; background-color: #f1f8ff; border-radius: 10px; padding: 20px;">

  <div class="accordion-item" style="border: none; background-color: transparent;">
    <h2 class="accordion-header" id="faq1-heading">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
        What is QGen?
      </button>
    </h2>
    <div  id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
      <div class="accordion-body" style="text-align:left;">
        QGen is a PHP-based tool that instantly generates SQL queries and ready-to-use PHP functions for any database table—saving hours of manual coding.
      </div>
    </div>
  </div>

  <div class="accordion-item" style="border: none; background-color: transparent;">
    <h2 class="accordion-header" id="faq2-heading">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
        Do I need to know PHP or SQL to use QGen?
      </button>
    </h2>
    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
      <div class="accordion-body" style="text-align:left;" >
        No. Just enter your table name and columns, select a task (like insert, update, delete, search), and QGen automatically builds the correct query and PHP code.
      </div>
    </div>
  </div>

  <div class="accordion-item" style="border: none; background-color: transparent;">
    <h2 class="accordion-header" id="faq3-heading">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
        What kind of operations can QGen generate?
      </button>
    </h2>
    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
      <div class="accordion-body" style="text-align:left;">
        QGen supports CRUD operations, search, sort, pagination, aggregation (COUNT, SUM, AVG), filters, and even advanced joins—each with properly formatted PHP functions.
      </div>
    </div>
  </div>

  <div class="accordion-item" style="border: none; background-color: transparent;">
    <h2 class="accordion-header" id="faq4-heading">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
        Can I customize or edit the generated code?
      </button>
    </h2>
    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
      <div class="accordion-body" style="text-align:left;">
        Yes. You can copy, tweak, and integrate the generated PHP code into your project. QGen simply saves you the time of writing everything from scratch.
      </div>
    </div>
  </div>

  <div class="accordion-item" style="border: none; background-color: transparent;">
    <h2 class="accordion-header" id="faq5-heading">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
        Does QGen save my previously generated queries?
      </button>
    </h2>
    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
      <div class="accordion-body" style="text-align:left;">
        Yes. After login, your query history is automatically saved so you can easily revisit or modify previous code snippets anytime.
      </div>
    </div>
  </div>

</div>

    </section>
  </main>

 <!-- Modals -->
  <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-3">
        <div class="modal-header">
          <h5 class="modal-title" id="loginModalLabel">Login to QGen</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <?php if (!empty($login_general_error)): ?>
            <div class="alert alert-danger text-center mb-3"><?= $login_general_error ?></div>
          <?php endif; ?>
          <form method="POST" action="">
            <div class="mb-3">
              <label for="emailInput" class="form-label">Email address</label>
              <input type="email" name="email_id" id="emailInput" class="form-control <?= !empty($login_email_error) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($email) ?>" placeholder="Enter your email">
              <?php if (!empty($login_email_error)): ?><div class="invalid-feedback"><?= $login_email_error ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
              <label for="passwordInput" class="form-label">Password</label>
              <input type="password" name="password" id="passwordInput" class="form-control <?= !empty($login_password_error) ? 'is-invalid' : '' ?>" placeholder="Enter your password">
              <?php if (!empty($login_password_error)): ?><div class="invalid-feedback"><?= $login_password_error ?></div><?php endif; ?>
            </div>
            <!-- FORGOT PASSWORD LINK ADDED HERE -->
            <div class="text-end mb-3">
                <a href="#" onclick="openForgotPassword()" class="text-decoration-none" style="font-size: 14px;">Forgot Password?</a>
            </div>
            <button type="submit" name="login_btn" class="btn btn-primary w-100">Login</button>
            <div class="text-center mt-3">
              <span style="font-size: 14px;">Don't have an account?</span>
              <a href="#" onclick="openSignup()" class="text-decoration-none">Sign up here</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="signupModal" tabindex="-1" aria-labelledby="signupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-3">
        <div class="modal-header">
          <h5 class="modal-title" id="signupModalLabel">Create a New Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form action="" method="POST">
            <div class="mb-3">
              <label for="signupEmail" class="form-label">Email address</label>
              <input type="email" name="email_id" id="signupEmail" class="form-control <?= $signup_email_error ? 'is-invalid' : '' ?>" placeholder="Enter your email" value="<?= htmlspecialchars($email) ?>" required>
              <?php if ($signup_email_error): ?><div class="invalid-feedback"><?= $signup_email_error ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
              <label for="signupPassword" class="form-label">Password</label>
              <input type="password" name="password" id="signupPassword" class="form-control <?= $signup_password_error ? 'is-invalid' : '' ?>" placeholder="Create a password" required>
              <?php if ($signup_password_error): ?><div class="invalid-feedback"><?= $signup_password_error ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
              <label for="confirmPassword" class="form-label">Confirm Password</label>
              <input type="password" name="confirm_password" id="confirmPassword" class="form-control <?= $signup_confirm_password_error ? 'is-invalid' : '' ?>" placeholder="Confirm your password" required>
              <?php if ($signup_confirm_password_error): ?><div class="invalid-feedback"><?= $signup_confirm_password_error ?></div><?php endif; ?>
            </div>
            <button type="submit" name="submit_btn" class="btn btn-success w-100 mb-2">Sign Up</button>
            <div class="text-center mt-2">
              <span style="font-size: 14px;">Already have an account?</span>
              <a href="#" class="text-decoration-none" onclick="openLogin()">Login here</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  
  <!-- ================== FORGOT PASSWORD MODAL ADDED HERE ================== -->
  <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-3">
        <div class="modal-header">
          <h5 class="modal-title" id="forgotPasswordModalLabel">Reset Your Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-center text-muted small">Enter your email address and we will send you a link to reset your password.</p>
          <form action="" method="POST"> <!-- NOTE: Add PHP logic for password reset -->
            <div class="mb-3">
              <label for="forgotEmail" class="form-label">Email address</label>
              <input type="email" name="forgot_email" id="forgotEmail" class="form-control" placeholder="Enter your registered email" required>
            </div>
            <button type="submit" name="forgot_btn" class="btn btn-warning w-100 mb-2">Send Reset Link</button>
            <div class="text-center mt-2">
              <a href="#" class="text-decoration-none" onclick="openLogin()">← Back to Login</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer style="background-color: #343a40; color: #fff; padding: 30px 0; text-align: center; width: 100%;">
    <div class="container">
      <div class="row justify-content-center text-center text-md-start">
        <div class="col-md-4 mb-3 d-flex flex-column align-items-center align-items-md-start">
          <!-- Corrected Image Path -->
          <img src="<?php echo BASE_URL; ?>aseets\img\sql-server-icon-png-29.png" alt="QGen Logo" width="50" style="margin-bottom: 10px;">
          <h5>QGen</h5>
          <p style="font-size: 14px;">Effortless SQL Generation for Everyone</p>
        </div>
        <div class="col-md-4 mb-3">
          <h6>Quick Links</h6>
          <ul class="list-unstyled" style="font-size: 14px;">
            <li><a href="index.php" style="color: #bbb; text-decoration: none;">Home</a></li>
            <li><a href="pages/features.php" style="color: #bbb; text-decoration: none;">Features</a></li>
            <li><a href="pages/tutorials.php" style="color: #bbb; text-decoration: none;">Tutorials</a></li>
            <li><a href="pages/about.php" style="color: #bbb; text-decoration: none;">Contact</a></li>
          </ul>
        </div>
        <div class="col-md-4 mb-3">
          <h6>Contact</h6>
          <p style="font-size: 14px;">Email: support@qgen.com</p>
          <p style="font-size: 14px;">Phone: +91-123-456-7890</p>
        </div>
      </div>
      <hr style="border-color: #555; margin: 20px auto; width: 90%;">
      <p style="margin: 0; font-size: 13px;">&copy; <?php echo date("Y"); ?> QGen. All rights reserved.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q"
    crossorigin="anonymous"></script>

  <?php if ($show_login_modal): ?>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
      loginModal.show();
    });
  </script>
  <?php endif; ?>

  <?php if ($show_signup_modal): ?>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      var signupModal = new bootstrap.Modal(document.getElementById('signupModal'));
      signupModal.show();
    });
  </script>
  <?php endif; ?>

  <script>
    function showSignupModal() {
      var signupModal = new bootstrap.Modal(document.getElementById('signupModal'));
      signupModal.show();
    }
    
    function openSignup() {
      const loginModalEl = document.getElementById('loginModal');
      const loginModal = bootstrap.Modal.getInstance(loginModalEl);
      if (loginModal) {
        loginModal.hide();
        // Bootstrap modals have a fade transition. We listen for it to end.
        loginModalEl.addEventListener('hidden.bs.modal', function () {
            const signupModal = new bootstrap.Modal(document.getElementById('signupModal'));
            signupModal.show();
        }, { once: true });
      }
    }
    
    function openLogin() {
      const signupModalEl = document.getElementById('signupModal');
      const signupModal = bootstrap.Modal.getInstance(signupModalEl);
      if (signupModal) {
          signupModal.hide();
          signupModalEl.addEventListener('hidden.bs.modal', function () {
            const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
            loginModal.show();
        }, { once: true });
      }
    }


      function openForgotPassword() {
        const loginModalEl = document.getElementById('loginModal');
        const loginModal = bootstrap.Modal.getInstance(loginModalEl);
        if (loginModal) {
            loginModal.hide();
            loginModalEl.addEventListener('hidden.bs.modal', function() {
                const forgotModal = new bootstrap.Modal(document.getElementById('forgotPasswordModal'));
                forgotModal.show();
            }, { once: true });
        }
    }
  </script>
</body>
</html>