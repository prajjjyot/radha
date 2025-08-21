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

    header("Location: pages/profile.php");
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

    header("Location: pages\profile.php");
    exit();
} else {
    $login_general_error = "Invalid email or password.";
}

    
}
?>



<?php
$generatedQuery = "";
$sqlQueryLine = "";

if (isset($_POST['generate_btn'])) {
    $task = strtolower(trim($_POST['task_name'] ?? ''));
    $table = trim($_POST['table_name']);
    $columns = trim($_POST['columns']);
    $columnsArr = array_filter(array_map('trim', explode(",", $columns)));
    $firstCol = $columnsArr[0] ?? 'id';

    switch ($task) {
        case 'insert_data':
            $func = "insert_{$table}_data";
            $vars = array_map(fn($c) => "\$var_$c", $columnsArr);
            $funcArgs = implode(", ", $vars);
            $binds = str_repeat("s", count($columnsArr));
            $bindParams = implode(", ", $vars);
            $placeholders = implode(", ", array_fill(0, count($columnsArr), "?"));
            $columnStr = implode(", ", $columnsArr);
            $sqlQueryLine = "INSERT INTO $table ($columnStr) VALUES ($placeholders);";
            $generatedQuery = <<<PHP
function $func($funcArgs)
{
    if (\$stmt = \$this->con->prepare("INSERT INTO $table ($columnStr) VALUES ($placeholders)")) {
        \$stmt->bind_param("$binds", $bindParams);
        return \$stmt->execute();
    }
    return false;
}
PHP;
            break;

        case 'get_all':
            $columnStr = implode(", ", $columnsArr);
            $resVars = array_map(fn($c) => "\$res_$c", $columnsArr);
            $resVarsStr = implode(", ", $resVars);
            $sqlQueryLine = "SELECT $columnStr FROM $table;";
            $rowSet = "";
            foreach ($columnsArr as $col) {
                $rowSet .= "            \$data[\$row_no]['$col'] = \$res_$col;\n";
            }
            $func = "get_{$table}_data";
            $generatedQuery = <<<PHP
function $func()
{
    if (\$stmt = \$this->con->prepare("SELECT $columnStr FROM $table")) {
        \$stmt->execute();
        \$stmt->bind_result($resVarsStr);
        \$data = [];
        \$row_no = 0;
        while (\$stmt->fetch()) {
$rowSet            \$row_no++;
        }
        return \$data;
    }
    return false;
}
PHP;
            break;

        case 'update_by_id':
            $func = "update_{$table}_by_id";
            $vars = array_map(fn($c) => "\$var_$c", $columnsArr);
            $funcArgs = implode(", ", array_merge($vars, ['\$id']));
            $binds = str_repeat("s", count($columnsArr)) . "s";
            $bindParams = implode(", ", array_merge($vars, ['\$id']));
            $setCols = implode(", ", array_map(fn($c) => "$c = ?", $columnsArr));
            $sqlQueryLine = "UPDATE $table SET $setCols WHERE $firstCol = ?;";
            $generatedQuery = <<<PHP
function $func($funcArgs)
{
    if (\$stmt = \$this->con->prepare("UPDATE $table SET $setCols WHERE $firstCol = ?")) {
        \$stmt->bind_param("$binds", $bindParams);
        return \$stmt->execute();
    }
    return false;
}
PHP;
            break;

        case 'delete_by_id':
            $sqlQueryLine = "DELETE FROM $table WHERE $firstCol = ?;";
            $generatedQuery = <<<PHP
function delete_{$table}_by_id(\$id)
{
    if (\$stmt = \$this->con->prepare("DELETE FROM $table WHERE $firstCol = ?")) {
        \$stmt->bind_param("s", \$id);
        return \$stmt->execute();
    }
    return false;
}
PHP;
            break;

        case 'get_by_column':
            $sqlQueryLine = "SELECT * FROM $table WHERE \$column = ?;";
            $generatedQuery = <<<PHP
function get_{$table}_by_column(\$column, \$value)
{
    if (\$stmt = \$this->con->prepare("SELECT * FROM $table WHERE \$column = ?")) {
        \$stmt->bind_param("s", \$value);
        \$stmt->execute();
        return \$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    return false;
}
PHP;
            break;

        case 'search_data':
            $searchLike = implode(" OR ", array_map(fn($c) => "$c LIKE CONCAT('%', ?, '%')", $columnsArr));
            $sqlQueryLine = "SELECT * FROM $table WHERE $searchLike;";
            $binds = str_repeat("s", count($columnsArr));
            $vars = implode(", ", array_fill(0, count($columnsArr), "\$keyword"));
            $generatedQuery = <<<PHP
function search_{$table}_data(\$keyword)
{
    \$query = "SELECT * FROM $table WHERE $searchLike";
    if (\$stmt = \$this->con->prepare(\$query)) {
        \$stmt->bind_param("$binds", $vars);
        \$stmt->execute();
        return \$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    return false;
}
PHP;
            break;

        case 'get_sorted':
            $sqlQueryLine = "SELECT * FROM $table ORDER BY \$column \$direction;";
            $generatedQuery = <<<PHP
function get_{$table}_sorted(\$column, \$direction = "ASC")
{
    \$query = "SELECT * FROM $table ORDER BY \$column \$direction";
    return \$this->con->query(\$query)->fetch_all(MYSQLI_ASSOC);
}
PHP;
            break;

        case 'get_paginated':
            $sqlQueryLine = "SELECT * FROM $table LIMIT ? OFFSET ?;";
            $generatedQuery = <<<PHP
function get_{$table}_data_paginated(\$limit, \$offset)
{
    \$query = "SELECT * FROM $table LIMIT ? OFFSET ?";
    if (\$stmt = \$this->con->prepare(\$query)) {
        \$stmt->bind_param("ii", \$limit, \$offset);
        \$stmt->execute();
        return \$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    return false;
}
PHP;
            break;

        case 'count_all':
            $sqlQueryLine = "SELECT COUNT(*) as total FROM $table;";
            $generatedQuery = <<<PHP
function count_{$table}_records()
{
    \$result = \$this->con->query("SELECT COUNT(*) as total FROM $table");
    return \$result->fetch_assoc()['total'] ?? 0;
}
PHP;
            break;

        case 'count_by_column_value':
            $sqlQueryLine = "SELECT COUNT(*) as total FROM $table WHERE \$column = ?;";
            $generatedQuery = <<<PHP
function count_{$table}_by_column_value(\$column, \$value)
{
    if (\$stmt = \$this->con->prepare("SELECT COUNT(*) as total FROM $table WHERE \$column = ?")) {
        \$stmt->bind_param("s", \$value);
        \$stmt->execute();
        return \$stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }
    return 0;
}
PHP;
            break;

        case 'max_column':
            $sqlQueryLine = "SELECT MAX(\$column) as max_val FROM $table;";
            $generatedQuery = <<<PHP
function get_max_column_from_{$table}(\$column)
{
    \$query = "SELECT MAX(\$column) as max_val FROM $table";
    return \$this->con->query(\$query)->fetch_assoc()['max_val'] ?? null;
}
PHP;
            break;

        case 'min_column':
            $sqlQueryLine = "SELECT MIN(\$column) as min_val FROM $table;";
            $generatedQuery = <<<PHP
function get_min_column_from_{$table}(\$column)
{
    \$query = "SELECT MIN(\$column) as min_val FROM $table";
    return \$this->con->query(\$query)->fetch_assoc()['min_val'] ?? null;
}
PHP;
            break;

        case 'check_existence':
            $sqlQueryLine = "SELECT 1 FROM $table WHERE \$column = ? LIMIT 1;";
            $generatedQuery = <<<PHP
function check_{$table}_existence(\$column, \$value)
{
    if (\$stmt = \$this->con->prepare("SELECT 1 FROM $table WHERE \$column = ? LIMIT 1")) {
        \$stmt->bind_param("s", \$value);
        \$stmt->execute();
        return \$stmt->get_result()->num_rows > 0;
    }
    return false;
}
PHP;
            break;

        case 'truncate_table':
            $sqlQueryLine = "TRUNCATE TABLE $table;";
            $generatedQuery = <<<PHP
function truncate_{$table}()
{
    return \$this->con->query("TRUNCATE TABLE $table");
}
PHP;
            break;

        case 'get_latest_record':
            $sqlQueryLine = "SELECT * FROM $table ORDER BY $firstCol DESC LIMIT 1;";
            $generatedQuery = <<<PHP
function get_latest_{$table}_record()
{
    \$query = "SELECT * FROM $table ORDER BY $firstCol DESC LIMIT 1";
    return \$this->con->query(\$query)->fetch_assoc();
}
PHP;
            break;

        case 'get_distinct_column':
            $sqlQueryLine = "SELECT DISTINCT \$column FROM $table;";
            $generatedQuery = <<<PHP
function get_distinct_column_from_{$table}(\$column)
{
    \$query = "SELECT DISTINCT \$column FROM $table";
    return \$this->con->query(\$query)->fetch_all(MYSQLI_ASSOC);
}
PHP;
            break;

        case 'get_random_record':
            $sqlQueryLine = "SELECT * FROM $table ORDER BY RAND() LIMIT 1;";
            $generatedQuery = <<<PHP
function get_random_{$table}_record()
{
    \$query = "SELECT * FROM $table ORDER BY RAND() LIMIT 1";
    return \$this->con->query(\$query)->fetch_assoc();
}
PHP;
            break;

        case 'soft_delete':
            $sqlQueryLine = "UPDATE $table SET is_deleted = 1 WHERE $firstCol = ?;";
            $generatedQuery = <<<PHP
function soft_delete_{$table}_record(\$id)
{
    if (\$stmt = \$this->con->prepare("UPDATE $table SET is_deleted = 1 WHERE $firstCol = ?")) {
        \$stmt->bind_param("s", \$id);
        return \$stmt->execute();
    }
    return false;
}
PHP;

            
            break;


            case 'batch_insert':
    $colStr = implode(", ", $columnsArr);
    $placeholderGroup = "(" . implode(", ", array_fill(0, count($columnsArr), "?")) . ")";
    $sqlQueryLine = "INSERT INTO $table ($colStr) VALUES ($placeholderGroup), ...;";

    $generatedQuery = <<<PHP
function batch_insert_{$table}_data(\$dataArray)
{
    \$query = "INSERT INTO $table ($colStr) VALUES ";
    \$placeholders = [];
    \$bindValues = [];
    foreach (\$dataArray as \$row) {
        \$placeholders[] = "(" . implode(",", array_fill(0, count(\$row), "?")) . ")";
        foreach (\$row as \$val) {
            \$bindValues[] = \$val;
        }
    }
    \$query .= implode(",", \$placeholders);
    if (\$stmt = \$this->con->prepare(\$query)) {
        \$types = str_repeat("s", count(\$bindValues));
        \$stmt->bind_param(\$types, ...\$bindValues);
        return \$stmt->execute();
    }
    return false;
}
PHP;
    break;


    case 'batch_delete':
    $sqlQueryLine = "DELETE FROM $table WHERE $firstCol IN (?, ?, ...);";
    $generatedQuery = <<<PHP
function batch_delete_{$table}_records_by_ids(\$idArray)
{
    if (empty(\$idArray)) return false;
    \$placeholders = implode(',', array_fill(0, count(\$idArray), '?'));
    \$query = "DELETE FROM $table WHERE $firstCol IN (\$placeholders)";
    if (\$stmt = \$this->con->prepare(\$query)) {
        \$types = str_repeat("s", count(\$idArray));
        \$stmt->bind_param(\$types, ...\$idArray);
        return \$stmt->execute();
    }
    return false;
}
PHP;
    break;



    case 'log_changes':
    $sqlQueryLine = "INSERT INTO log_table (table_name, record_id, action, changed_data, timestamp) VALUES (?, ?, ?, ?, NOW());";
    $generatedQuery = <<<PHP
function log_{$table}_changes(\$record_id, \$action, \$changed_data)
{
    \$query = "INSERT INTO log_table (table_name, record_id, action, changed_data, timestamp) VALUES (?, ?, ?, ?, NOW())";
    if (\$stmt = \$this->con->prepare(\$query)) {
        \$tableName = "$table";
        \$stmt->bind_param("ssss", \$tableName, \$record_id, \$action, \$changed_data);
        return \$stmt->execute();
    }
    return false;
}
PHP;
    break;




    case 'archive_record':
    $sqlQueryLine = "INSERT INTO {$table}_backup SELECT * FROM $table WHERE $firstCol = ?;";
    $generatedQuery = <<<PHP
function archive_{$table}_record_to_backup_table(\$id)
{
    \$query = "INSERT INTO {$table}_backup SELECT * FROM $table WHERE $firstCol = ?";
    if (\$stmt = \$this->con->prepare(\$query)) {
        \$stmt->bind_param("s", \$id);
        return \$stmt->execute();
    }
    return false;
}
PHP;
    break;


        default:
            $sqlQueryLine = "-- Unknown or unsupported task.";
            $generatedQuery = "⚠️ Task not implemented or invalid.";
    }


    if (!empty($_SESSION['user_id']) && isset($_POST['generate_btn'])) {
    $db->save_history(
        (int)$_SESSION['user_id'],
        'advanced',            // generator type
        $task ?? null,         // task name
        $table,                // table name
        $columns,              // columns input
        $sqlQueryLine ?? null, // one-liner SQL
        $generatedQuery ?? '', // full generated query
        $generatedQuery ?? ''  // store the generated PHP functions as well
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
              <a class="nav-link" href="features.php">Features</a>
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
    <a href="pages/profile.php" class="btn btn-outline-secondary w-100 mb-2">My Profile</a>
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
     
  <a href="profile.php">
  <img src="<?php echo htmlspecialchars('../aseets/img/' . $avatarFile); ?>" 
     alt="User Avatar" 
     style="width:40px; height:40px; border-radius:50%; object-fit: cover;">
    </a>

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
          <a class="nav-link" href="../index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="features.php">Features</a>
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
  </br>
  <!--Hero Section-->
  <section class="hero-section">
    <div class="d-flex align-items-center justify-content-center flex-column"
      style="padding-top: 70px; text-align: center;">
      <h1 style="font-size: 2.5rem; text-align: center; font-weight: bold; color:black;">
  Advanced Code Generation — Build <span style="
    background: linear-gradient(270deg, #ff6a00, #ee0979, #007bff, #00c6ff);
    background-size: 600% 600%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    color: transparent;
    animation: gradientMove 6s ease infinite;
    display: inline-block;
  ">
    25+ SQL Queries &amp; PHP Functions
  </span> in One Click
</h1>

    </div>




    
      <div class="form-blocker" style="position: relative;">

  <?php if (empty($_SESSION['user_id'])): ?>
    <div onclick="showSignupModal()" 
         style="
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(255,255,255,0.0); /* fully transparent but clickable */
            z-index: 1000; 
            cursor: pointer;">
    </div>
  <?php endif; ?>


<form method="POST" action="">
  <div class="form-container">
    <div class="input-group" style="display: flex; flex-direction: column; gap: 10px;">

      
      <!-- Task Dropdown + Manual -->
      <label for="task_name" style="text-align: center; display: block;">Select Task or Type Manually:</label>

      <input list="tasks" name="task_name" id="task_name" placeholder="Start typing or select task..." style="padding:10px; width:100%;" autocomplete="off" required value="<?php echo htmlspecialchars($_POST['task_name'] ?? ''); ?>">

<datalist id="tasks">
  <optgroup label="🔴 BASIC CRUD">
    <option value="insert_data">insert_{table}_data</option>
    <option value="get_all">get_{table}_data</option>
    <option value="get_by_id">get_{table}_by_id</option>
    <option value="update_by_id">update_{table}_by_id</option>
    <option value="delete_by_id">delete_{table}_by_id</option>
  </optgroup>
  <optgroup label="🟡 FILTERS, SEARCH & SORT">
    <option value="get_by_column">get_{table}_by_column</option>
    <option value="search_data">search_{table}_data</option>
    <option value="get_sorted">get_{table}_sorted</option>
    <option value="get_paginated">get_{table}_data_paginated</option>
  </optgroup>
  <optgroup label="🟣 AGGREGATION">
    <option value="count_all">count_{table}_records</option>
    <option value="count_by_column_value">count_{table}_by_column_value</option>
    <option value="max_column">get_max_{column}_from_{table}</option>
    <option value="min_column">get_min_{column}_from_{table}</option>
  </optgroup>
  <optgroup label="🔵 VALIDATION / UTILITIES">
    <option value="check_existence">check_{table}_existence</option>
    <option value="truncate_table">truncate_{table}</option>
    <option value="get_latest_record">get_latest_{table}_record</option>
    <option value="export_to_csv">export_{table}_to_csv</option>
    <option value="get_distinct_column">get_distinct_{column}_from_{table}</option>
    <option value="get_random_record">get_random_{table}_record</option>
  </optgroup>
  <optgroup label="🟤 ADVANCED">
    <option value="soft_delete">soft_delete_{table}_record</option>
    <option value="batch_insert">batch_insert_{table}_data</option>
    <option value="batch_delete">batch_delete_{table}_records_by_ids</option>
    <option value="log_changes">log_{table}_changes</option>
    <option value="archive_record">archive_{table}_record_to_backup_table</option>
  </optgroup>
</datalist>



      <!-- Table Name -->
       <label for="task_name" style="text-align: center; display: block;">Enter table name:</label>
      <input type="text" name="table_name" placeholder="Enter table name" style="padding: 10px;" required value="<?php echo htmlspecialchars($_POST['table_name'] ?? ''); ?>">

      <!-- Columns -->
       <label for="task_name" style="text-align: center; display: block;">Enter columns comma-separated:</label>
      <input name="columns" placeholder="Enter columns comma-separated" style="padding: 10px; height: 80px;" required value="<?php echo htmlspecialchars($_POST['columns'] ?? ''); ?>">


          <button class="full-width-btn" type="submit" name="generate_btn">Generate Queries & Function</button>
      <?php if (!empty($sqlQueryLine)): ?>
  <div style="position: relative; margin-top: 20px;">
    <button onclick="copySQLLine()" type="button" style="
      position: absolute;
      top: 10px;
      right: 10px;
      padding: 6px 12px;
      font-size: 13px;
      border: none;
      background-color: green;
      color: #fff;
      border-radius: 5px;
      cursor: pointer;
    ">Copy SQL</button>

    <pre id="query-line" style="
      background-color: #e6ffe6;
      padding: 15px;
      border-radius: 6px;
      border: 1px solid #ccc;
      color: #000;
      font-size: 15px;
      overflow-x: auto;
      white-space: pre-wrap;
    "><?php echo htmlspecialchars($sqlQueryLine); ?></pre>
  </div>
<?php endif; ?>


      <!-- Output Section -->
      <div style="position: relative; margin-top: 10px;">
        <button id="copyBtn" onclick="copySQL()" type="button" style="
          position: absolute;
          top: 10px;
          right: 10px;
          padding: 6px 12px;
          font-size: 13px;
          border: none;
          background-color: #007bff;
          color: #fff;
          border-radius: 5px;
          cursor: pointer;
        ">Copy</button>

        <pre id="generated-sql" style="
          background-color: #f8f9fa;
          padding: 15px;
          border-radius: 6px;
          border: 1px solid #ccc;
          color: #000;
          font-size: 15px;
          overflow-x: auto;
          height: 200px;
          white-space: pre-wrap;
        "><?php echo htmlspecialchars($generatedQuery ?? ''); ?></pre>
      </div>
    </div>

    
  </div>
</form>

<script>
  function copySQL() {
    const text = document.getElementById("generated-sql").innerText;
    navigator.clipboard.writeText(text);
    alert("Copied to clipboard!");
  }
</script>

<script>
  function copySQLLine() {
    const text = document.getElementById("query-line").innerText;
    navigator.clipboard.writeText(text);
    alert("SQL query copied!");
  }
</script>





        
  </div>
  <!-- Login Modal -->
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

        <?php if (!empty($login_success)): ?>
          <div class="alert alert-success text-center mb-3"><?= $login_success ?></div>
        <?php endif; ?>

        <form method="POST" action="">
          <div class="mb-3">
            <label for="emailInput" class="form-label">Email address</label>
            <input type="email" name="email_id" id="emailInput"
              class="form-control <?= !empty($login_email_error) ? 'is-invalid' : '' ?>"
              value="<?= htmlspecialchars($email) ?>" placeholder="Enter your email">
            <?php if (!empty($login_email_error)): ?>
              <div class="invalid-feedback"><?= $login_email_error ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label for="passwordInput" class="form-label">Password</label>
            <input type="password" name="password" id="passwordInput"
              class="form-control <?= !empty($login_password_error) ? 'is-invalid' : '' ?>"
              placeholder="Enter your password">
            <?php if (!empty($login_password_error)): ?>
              <div class="invalid-feedback"><?= $login_password_error ?></div>
            <?php endif; ?>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
              <label class="form-check-label" for="rememberMe">Remember me</label>
            </div>
            <a href="#" class="text-decoration-none" style="font-size: 14px;" onclick="openForgotPassword()">Forgot password?</a>
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

<!-- Sign Up Modal -->
<div class="modal fade" id="signupModal" tabindex="-1" aria-labelledby="signupModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-3">
      <div class="modal-header">
        <h5 class="modal-title" id="signupModalLabel">Create a New Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?= $signup_general_message ?>
        <form action="" method="POST">
          <div class="mb-3">
            <label for="signupEmail" class="form-label">Email address</label>
            <input type="email" name="email_id" id="signupEmail"
              class="form-control <?= $signup_email_error ? 'is-invalid' : '' ?>"
              placeholder="Enter your email" value="<?= htmlspecialchars($email) ?>" required>
            <?php if ($signup_email_error): ?>
              <div class="invalid-feedback"><?= $signup_email_error ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label for="signupPassword" class="form-label">Password</label>
            <input type="password" name="password" id="signupPassword"
              class="form-control <?= $signup_password_error ? 'is-invalid' : '' ?>"
              placeholder="Create a password" value="<?= htmlspecialchars($password) ?>" required>
            <?php if ($signup_password_error): ?>
              <div class="invalid-feedback"><?= $signup_password_error ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label for="confirmPassword" class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirmPassword"
              class="form-control <?= $signup_confirm_password_error ? 'is-invalid' : '' ?>"
              placeholder="Confirm your password" value="<?= htmlspecialchars($confirm_password) ?>" required>
            <?php if ($signup_confirm_password_error): ?>
              <div class="invalid-feedback"><?= $signup_confirm_password_error ?></div>
            <?php endif; ?>
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

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-3">
      <div class="modal-header">
        <h5 class="modal-title" id="forgotPasswordModalLabel">Reset Your Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if (!empty($forgot_message)): ?>
          <div class="alert alert-info text-center"><?= $forgot_message ?></div>
        <?php endif; ?>

        <form method="POST" action="">
          <div class="mb-3">
            <label for="forgotEmail" class="form-label">Enter your registered email</label>
            <input type="email" class="form-control <?= !empty($forgot_email_error) ? 'is-invalid' : '' ?>"
              id="forgotEmail" name="forgot_email" placeholder="your@email.com"
              value="<?= htmlspecialchars($forgot_email ?? '') ?>" required>
            <?php if (!empty($forgot_email_error)): ?>
              <div class="invalid-feedback"><?= $forgot_email_error ?></div>
            <?php endif; ?>
          </div>

          <button type="submit" name="forgot_btn" class="btn btn-warning w-100">Send Reset Link</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Script to handle all modal switching -->
<script>
  function openSignup() {
    const current = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
    if (current) current.hide();

    document.getElementById('loginModal').addEventListener('hidden.bs.modal', function () {
      const signup = new bootstrap.Modal(document.getElementById('signupModal'));
      signup.show();
    }, { once: true });
  }

  function openLogin() {
    const current = bootstrap.Modal.getInstance(document.getElementById('signupModal'));
    if (current) current.hide();

    document.getElementById('signupModal').addEventListener('hidden.bs.modal', function () {
      const login = new bootstrap.Modal(document.getElementById('loginModal'));
      login.show();
    }, { once: true });
  }

  function openForgotPassword() {
    const loginModalEl = document.getElementById('loginModal');
    const loginInstance = bootstrap.Modal.getInstance(loginModalEl);
    if (loginInstance) loginInstance.hide();

    // Listen for login modal fully hidden
    loginModalEl.addEventListener('hidden.bs.modal', function () {
      // Clean leftover backdrops
      document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
      document.body.classList.remove('modal-open');
      document.body.style = '';

      const forgotModal = new bootstrap.Modal(document.getElementById('forgotPasswordModal'));
      forgotModal.show();
    }, { once: true });
  }
</script>

</section>
</br>
  
  <!-- Footer -->
 <footer style="background-color: #343a40; color: #fff; padding: 30px 0; text-align: center; width: 100%;">
    <!-- <div class="container-fluid px-0"> -->
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
    <!-- </div> -->
  </footer>

      <?php if (!empty($login_email_error) || !empty($login_password_error) || !empty($login_general_error) || !empty($login_success)): ?>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
    loginModal.show();
  });
</script>
<?php endif; ?>

<?php if ($signup_email_error || $signup_password_error || $signup_confirm_password_error || $signup_general_message): ?>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    var signupModal = new bootstrap.Modal(document.getElementById('signupModal'));
    signupModal.show();
  });
</script>
<?php endif; ?>






</body>

<script>
  function showSignupModal() {
    var signupModal = new bootstrap.Modal(document.getElementById('signupModal'));
    signupModal.show();
  }
</script>





<script>
  function copySQL() {
    const sql = document.getElementById("generated-sql").innerText;
    const copyBtn = document.getElementById("copyBtn");

    navigator.clipboard.writeText(sql).then(() => {
      copyBtn.textContent = "Copied!";
      copyBtn.style.backgroundColor = "#28a745"; // Green for feedback

      setTimeout(() => {
        copyBtn.textContent = "Copy";
        copyBtn.style.backgroundColor = "#007bff"; // Back to original
      }, 2000);
    });
  }
</script>



</html>