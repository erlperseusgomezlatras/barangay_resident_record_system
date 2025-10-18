<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Resident'])) {
  header("Location: login.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Resident Portal | Barangay Record System</title>
  <style>
    body { font-family: Arial; background: linear-gradient(to right, #6b73ff, #000dff); margin: 0; padding: 0; color: #333; }
    .container { background: #fff; max-width: 700px; margin: 60px auto; border-radius: 10px; padding: 30px; box-shadow: 0 3px 10px rgba(0,0,0,0.2); }
    h2 { color: #000dff; }
    .logout { float: right; text-decoration: none; color: red; }
  </style>
</head>
<body>
  <div class="container">
    <a href="logout.php" class="logout">Logout</a>
    <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
    <p>This is your Resident Portal.</p>
    <p>Here you can:</p>
    <ul>
      <li>View your registered information</li>
      <li>Check your Cedula issuance status</li>
      <li>Update personal details (if enabled)</li>
    </ul>
  </div>
</body>
</html>
