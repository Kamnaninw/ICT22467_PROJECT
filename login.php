<?php
session_start();
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

$message = "";

if (isset($_GET["registered"])) {
    $message = "สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $message = "กรุณากรอกอีเมลและรหัสผ่าน";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password_hash"])) {
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["role_name"] = $user["role_name"];

            header("Location: dashboard.php");
            exit;
        } else {
            $message = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>เข้าสู่ระบบ</title>
  <style>
    body{font-family:Arial,sans-serif;background:#eef3f9;margin:0;padding:40px}
    .box{max-width:460px;margin:auto;background:#fff;padding:30px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
    h1{margin-top:0;color:#1f4e98}
    input{width:100%;padding:12px;margin:8px 0 16px;border:1px solid #cfd8e3;border-radius:10px;box-sizing:border-box}
    button{background:#1f4e98;color:#fff;border:0;padding:12px 16px;border-radius:10px;cursor:pointer;font-weight:bold}
    a{color:#1f4e98;text-decoration:none}
    .msg{margin-bottom:16px;padding:12px;border-radius:10px;background:#eef7ff;color:#1f4e98}
    .msg.error{background:#fff3f3;color:#c0392b}
  </style>
</head>
<body>
  <div class="box">
    <h1>เข้าสู่ระบบ</h1>

    <?php if ($message !== ""): ?>
      <div class="msg <?php echo (strpos($message, 'ไม่') !== false || strpos($message, 'กรุณา') !== false) ? 'error' : ''; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <label>อีเมล</label>
      <input type="email" name="email" required>

      <label>รหัสผ่าน</label>
      <input type="password" name="password" required>

      <button type="submit">Login</button>
    </form>

    <p style="margin-top:16px;">
      ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a>
    </p>
  </div>
</body>
</html>
