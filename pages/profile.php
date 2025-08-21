<?php
session_start();

// Force browser to always revalidate
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['logged_in_email'])) {
    header("Location: ../index.php");
    exit();
}

// ✅ Set the $email variable
$email = $_SESSION['logged_in_email'];
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


    <style>
      .avatar-option img {
        border: 3px solid transparent;
        transition: border-color 0.3s ease;
      }
      .avatar-option input:checked + img {
        border-color: #007bff;
      }
      
      /* ================== FIX FOR NAVBAR TOGGLER ================== */
      /* This ensures the hamburger icon is always visible on mobile */
      .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(0, 0, 0, 0.55)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
      }
      .navbar-toggler {
        border-color: rgba(0,0,0,.1) !important;
      }
      /* ========================================================== */

    </style>


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
  <a class="nav-link" href="history.php">History</a>
</li>


          </ul>
          <!-- Show Login/Sign Up only on small screens -->

          <!-- <div class="d-lg-none mt-3">
            <a href="#" class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#loginModal"
              data-bs-dismiss="offcanvas">Login</a>
            <a href="#" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#signupModal"
              data-bs-dismiss="offcanvas">Sign Up</a>
          </div> -->
        </div>
      </div>
      <!-- Show only on large screens -->
      <div class="d-none d-lg-flex gap-2">
         <!-- Profile Icon -->
 <div class="d-flex align-items-center">
  <?php if (!empty($_SESSION['name'])): ?>
    <span style="font-family: 'Comic Sans MS', cursive; font-size: 16px; margin-right: 8px;">
      <?= htmlspecialchars($_SESSION['name']) ?>
    </span>
  <?php endif; ?>
  <a class="nav-link active p-0" aria-current="page" href="profile.php">
    <img src="../aseets/img/<?= $_SESSION['avatar'] ?? 'avatar1.jpg' ?>" alt="Profile"
         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
  </a>
</div>
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
  <a class="nav-link" href="history.php">History</a>
</li>
      </ul>
    </div>
  </div>

  <!--End Navbar-->
  <div class="container py-5 mt-5" style="margin-top: 100px;">

    <div class="card shadow p-4 mx-auto" style="max-width: 600px;">
      <h2 class="text-center mb-4">My Profile</h2>

        <?php if (!empty($_SESSION['password_message'])): ?>
          <div class="alert alert-info text-center">
            <?php 
              echo $_SESSION['password_message']; 
              unset($_SESSION['password_message']); 
            ?>
          </div>
        <?php endif; ?>

      <!-- User Info -->
      <form method="post" action="update_profile.php">
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" readonly class="form-control" value="<?= htmlspecialchars($email) ?>">
        </div>

        <div class="mb-3">
          <label for="name" class="form-label">Name</label>
          <input type="text" name="name" class="form-control"
       value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>"
       placeholder="Enter your nickname (optional)">

        </div>

        <div class="mb-3">
          <label class="form-label">Choose Your Avatar</label>
          <div class="d-flex flex-wrap gap-3">
            <?php
            $avatars = ["avatar1.png", "avatar2.png", "avatar3.png", "avatar5.png", "avatar6.png", "avatar7.png"];
            $selectedAvatar = $_SESSION['avatar'] ?? "avatar1.png";

            foreach ($avatars as $index => $avatar) {
              $isChecked = $avatar === $selectedAvatar ? "checked" : "";
              echo '
                <label class="avatar-option" style="text-align:center;">
                  <input type="radio" name="avatar" value="' . $avatar . '" style="display:none;" ' . $isChecked . '>
                  <img src="../aseets/img/' . $avatar . '" alt="Avatar ' . ($index + 1) . '" 
                       style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; cursor: pointer;">
                  <div style="font-size: 12px; margin-top: 4px;">Avatar ' . ($index + 1) . '</div>
                </label>';
            }
            ?>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Save Preferences</button>
      </form>

      <hr class="my-4">

      <!-- Change Password -->
      <form method="post" action="change_password.php">
        <h5 class="mb-3">Change Password</h5>
        <div class="mb-2">
          <input type="password" name="old_password" class="form-control" placeholder="Old Password" required>
        </div>
        <div class="mb-2">
          <input type="password" name="new_password" class="form-control" placeholder="New Password" required>
        </div>
        <div class="mb-3">
          <input type="password" name="confirm_password" class="form-control" placeholder="Confirm New Password" required>
        </div>
        <button type="submit" class="btn btn-warning w-100">Update Password</button>
      </form>

      <hr class="my-4">

      <!-- Logout -->
      <a href="logout.php" 
         style="display: block; width: 100%; padding: 10px; background-color: #dc3545; color: white; text-align: center; text-decoration: none; border: none; border-radius: 5px;">
         Logout
      </a>

    </div>
  </div>

  
  <!-- Footer -->
  <footer style="background-color: #343a40; color: #fff; padding: 30px 0; text-align: center; width: 100%;">
    <div class="container-fluid px-0">
      <div class="row justify-content-center text-center text-md-start" style="margin: 0; padding: 0 20px;">
        <!-- Logo and Brand Info -->
        <div class="col-md-4 mb-3 d-flex flex-column align-items-center align-items-md-start">
          <img src="../aseets\img\sql-server-icon-png-29.png" alt="QGen Logo" width="50" style="margin-bottom: 10px;">
          <h5>QGen</h5>
          <p style="font-size: 14px;">Effortless SQL Generation for Everyone</p>
        </div>

        <!-- Quick Links -->
        <div class="col-md-4 mb-3">
          <h6>Quick Links</h6>
          <ul class="list-unstyled" style="font-size: 14px;">
            <li><a href="../index.php" style="color: #bbb; text-decoration: none;">Home</a></li>
            <li><a href="features.php" style="color: #bbb; text-decoration: none;">Features</a></li>
            <li><a href="tutorials.php" style="color: #bbb; text-decoration: none;">Tutorials</a></li>
            <li><a href="about.php" style="color: #bbb; text-decoration: none;">Contact</a></li>
          </ul>
        </div>

        <!-- Contact Info -->
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

  <script>
  // Force reload on back/forward navigation
  window.addEventListener("pageshow", function (event) {
    if (event.persisted || performance.getEntriesByType("navigation")[0].type === "back_forward") {
      window.location.reload();
    }
  });
</script>

</body>
</html>