<?php
session_start();

/* -------------------------
   DATABASE CONNECTION
   ------------------------- */
$servername   = "localhost";
$db_username  = "root";
$db_password  = "";
$database     = "1_barangay_record";

$conn = new mysqli($servername, $db_username, $db_password, $database);
if ($conn->connect_error) {
  die("Database connection failed: " . $conn->connect_error);
}

/* -------------------------
   LOGIN LOGIC
   ------------------------- */
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($username === '' || $password === '') {
    $error = "Please fill in both fields.";
  } else {
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
      $user = $result->fetch_assoc();
      // support hashed password (preferred), fallback to plain (legacy)
      if (password_verify($password, $user['password']) || $user['password'] === $password) {
        // set session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // redirect by role
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
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Barangay Carmen — Login</title>

  <!-- Bootstrap (for icons + minor layout) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --green-dark: #064e3b;
      --green-base: #16a34a;
      --green-light: #34d399;
      --glass-bg: rgba(255,255,255,0.06);
      --text: #eafff4;
    }

    *{box-sizing: border-box}
    html,body{height:100%; margin:0; font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;}

    /* background: forest image + green overlay */
    body{
      min-height:100%;
      display:flex;
      flex-direction:column;
      background-image:
        linear-gradient(120deg, rgba(5,80,58,0.86) 0%, rgba(16,185,129,0.55) 60%),
        url('uploads/profile_images/forest-bg.png');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      color: var(--text);
    }

    /* subtle top bar */
    header.topbar {
      height: 78px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:1rem;
      padding: 0 1.4rem;
      background: linear-gradient(90deg, rgba(255,255,255,0.03), rgba(0,0,0,0.06));
      backdrop-filter: blur(6px);
      border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    header .brand { display:flex; align-items:center; gap:.8rem; }
    header .brand img{ height:64px; width:auto; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.35); background: rgba(255,255,255,0.02);}
    header h1{ margin:0; font-size:1.1rem; color:var(--text); letter-spacing:.06rem;}
    header .cta button{ background:transparent; border:1px solid rgba(255,255,255,0.08); color:var(--text); padding:.5rem .8rem; border-radius:10px; }

    /* center area */
    .stage {
      flex:1;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:2.2rem;
    }

    /* container layout: image panel + form panel (responsive stacks) */
    .card-wrap {
      display:grid;
      grid-template-columns: 1fr 420px;
      gap: 2rem;
      width: min(1100px, 96%);
      align-items: center;
    }

    /* left visual panel: forest silhouette + headline */
    .visual {
      border-radius: 16px;
      padding: 2rem;
      min-height: 380px;
      display:flex;
      flex-direction:column;
      justify-content:center;
      background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
      box-shadow: 0 14px 40px rgba(4,6,10,0.45);
      border: 1px solid rgba(255,255,255,0.04);
      color: var(--text);
    }
    .visual h2 { margin:0 0 .4rem 0; font-size:2.2rem; line-height:1.02; }
    .visual p { margin:0 0 1rem 0; opacity:.9; font-size:1rem; max-width:48ch; }
    .visual .feature-list { display:flex; gap:.6rem; flex-wrap:wrap; margin-top:1.1rem; }
    .pill { background: rgba(255,255,255,0.03); padding:.5rem .8rem; border-radius:999px; font-weight:600; border:1px solid rgba(255,255,255,0.03); }

    /* login panel (glass card) */
    .login {
      border-radius: 16px;
      padding: 50px;
      width: 600px;
      background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.03));
      backdrop-filter: blur(8px) saturate(120%);
      box-shadow: 0 12px 32px rgba(2,6,23,0.5);
      border: 1px solid rgba(255,255,255,0.08);
    }



    .login h3 { margin:0 0 .4rem 0; font-size:1.25rem; color:var(--text); }
    .login p { margin:0 0 1rem 0; color: rgba(235,255,244,0.85); font-size:.94rem; }

    .form-group { margin-bottom:.9rem; }
    .form-label { display:block; margin-bottom:.3rem; color: rgba(235,255,244,0.9); font-weight:700; }
    .input {
      width:100%;
      height:48px;
      padding:.5rem .75rem;
      border-radius:10px;
      border:none;
      background: rgba(255,255,255,0.04);
      color:var(--text);
    }
    .input::placeholder { color:rgba(230,255,244,0.55); }

    .input-row { display:flex; gap:.6rem; align-items:center; }
    .icon { display:inline-flex; align-items:center; justify-content:center; width:44px; height:44px; border-radius:10px; background: rgba(255,255,255,0.02); color: rgba(255,255,255,0.8); border:1px solid rgba(255,255,255,0.03); }

    .actions { margin-top:.8rem; display:flex; gap:.6rem; align-items:center; justify-content:space-between; }
    .login-btn {
      background: linear-gradient(90deg, var(--green-base), var(--green-light));
      color:#fff;
      border:none;
      padding:.65rem 1rem;
      border-radius:10px;
      font-weight:700;
      cursor:pointer;
      box-shadow: 0 8px 22px rgba(16,185,129,0.16);
    }
    .link-small { color: rgba(235,255,244,0.9); font-size:.92rem; text-decoration:underline; }

    .error {
      margin-top:.8rem;
      background: rgba(255,255,255,0.04);
      padding:.6rem .8rem;
      border-radius:8px;
      border-left:4px solid #ff6b6b;
      color:#ffdede;
    }

    footer.bottom {
      height:70px;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:.95rem;
      color: rgba(235,255,244,0.9);
      background: linear-gradient(90deg, rgba(0,0,0,0.03), rgba(255,255,255,0.01));
      border-top: 1px solid rgba(255,255,255,0.03);
    }

    /* small screens: stack */
    @media (max-width:920px){
      .card-wrap { grid-template-columns: 1fr; }
      .visual { order:2; padding:1.2rem; min-height:200px; }
      .login { order:1; }
    }

    @media (max-width:420px){
      header.topbar { padding:.5rem; }
      .visual h2 { font-size:1.4rem; }
      .input { height:44px; }
      .icon { width:40px; height:40px; }
    }
    section img {
      width: 88px;
      height: 88px;
      object-fit: cover;
      border-radius: 50%;
      border: 2px solid rgba(255,255,255,0.08);
      box-shadow: 0 6px 20px rgba(0,0,0,0.35);
    }

    .form-header { 
      display: flex;
      justify-content: space-between;
    }

  </style>
</head>
<body>



  <main class="stage" role="main">


      <!-- Login panel -->
      <section class="login" aria-labelledby="login-heading">
        <div class="form-header">
          <div>
            <h3 id="login-heading">Sign in</h3>
            <p>Enter your credentials to continue</p>
          </div>
          <div>
        <img src="uploads/profile_images/images.jpg" alt="">
          </div>
        </div>
        <?php if (!empty($error)): ?>
          <div class="error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" novalidate autocomplete="on" style="margin-top: .6rem;">
          <div class="form-group">
            <label for="username" class="form-label">Username</label>
            <div class="input-row">
              <div class="icon" aria-hidden="true"><i class="bi bi-person-fill"></i></div>
              <input id="username" name="username" type="text" class="input" placeholder="Username" required autofocus>
            </div>
          </div>

          <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <div class="input-row">
              <div class="icon" aria-hidden="true"><i class="bi bi-lock-fill"></i></div>
              <input id="password" name="password" type="password" class="input" placeholder="Password" required>
            </div>
          </div>

          <div class="actions">
            <div style="display:flex; align-items:center; gap:.45rem;">
              <input id="remember" name="remember" type="checkbox" style="width:16px; height:16px;">
              <label for="remember" style="font-weight:600; font-size:.95rem;">Remember me</label>
            </div>

            <div>
              <a class="link-small" href="forgot_password.php">Forgot password?</a>
            </div>
          </div>

          <div style="margin-top:1rem">
            <button class="login-btn" type="submit" aria-label="Login"><i class="bi bi-box-arrow-in-right" style="margin-right:.5rem"></i>Login</button>
          </div>

          <div style="margin-top:1rem; text-align:center; font-size:.95rem; color:rgba(235,255,244,0.9);">
            Don't have an account? <a href="register.php" class="link-small">Register here</a>
          </div>
        </form>
      </section>
    </div>
  </main>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
