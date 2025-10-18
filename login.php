<?php
session_start();

// DATABASE CONNECTION
$servername = "localhost";
$username = "root"; 
$password = "";     
$database = "1_barangay_record";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
  die("Database connection failed: " . $conn->connect_error);
}

// LOGIN LOGIC
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = $_POST['username'];
  $password = $_POST['password'];

  $sql = "SELECT * FROM users WHERE username = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password']) || $user['password'] === $password) {
      $_SESSION['user_id'] = $user['user_id'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['role'] = $user['role'];

      if (in_array($user['role'], ['Admin', 'Captain', 'Secretary', 'Treasurer', 'Kagawad', 'Staff'])) {
        header("Location: admin_portal.php");
      } else {
        header("Location: resident_portal.php");
      }
      exit();
    } else {
      $error = "Incorrect password!";
    }
  } else {
    $error = "User not found!";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Barangay Record Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<style>
  main {
    height: 700px;
    display: flex;
    justify-content: center;
  }


  header {
    display: flex;
    background-color: #28a745;
    width: 100%;
    height: 125px;
    align-items: center;
    justify-content: space-around;

  }
  header h1 { 
    color: white;
  }
  header img { 
    height: 90px;
    width: 350px;
  }
  header button{ 
    width: 125px;
    height: 40px;
    border-radius: 10px;
    border-color: green
    
  }

  .login-card { 
    align-items: center;
    display: flex;
    flex-direction: column;
    width: 50%;
    justify-content: center;
    margin-top: 20px;
    margin-bottom: 20px;
    border-radius: 25px;
    box-shadow: 5px 5px 5px 5px grey;
    width: 37%;
  }

  .login-card input { 
    width: 100%;
    height: 40px;
  }

  .login-card button { 
    margin-top: 25px;
    width: 100%;
    border-radius: none;
    background-color: green;
    height: 40px;
    color: white;
    border: 25px;
  }

  footer { 
    background-color: #28a745;
    height: 120px;
    align-items: center;
    display: flex;
    justify-content: center;
    color: white;
  }

  form { 
    width: 450px;
  }

  form h5 { 
    margin-top: 25px;
    align-items: center;
    justify-content: center;
    display: flex;
  }


</style>


<body>
<header>
  <img src="uploads/profile_images/CAGAYAN_DE_ORO_CITY_BP2 (1).png" alt="">
  <h1>BARANGAY CARMEN</h1>
  <button>Login</button>
</header>

<main>
  <div class="login-card">
    <img src="uploads/profile_images/images.jpg" alt="">
    <h1>Login</h1>
    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <h5>Username:</h5>
      <input type="text" name="username" placeholder="Username" required>
      <h5>Password:</h5>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit"><i class="bi bi-box-arrow-in-right"></i> Login</button>
    </form>
  </div>
</main>

<footer>
  &copy; <?= date("Y") ?> Barangay 1, CdeO City. All Rights Reserved
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
