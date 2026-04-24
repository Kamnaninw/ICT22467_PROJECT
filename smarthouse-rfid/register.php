<?php
require_once __DIR__ . '/config/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($full_name === "" || $email === "" || $password === "" || $confirm_password === "") {
        $message = "กรุณากรอกข้อมูลให้ครบ";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "รูปแบบอีเมลไม่ถูกต้อง";
    } elseif ($password !== $confirm_password) {
        $message = "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน";
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $message = "อีเมลนี้ถูกใช้งานแล้ว";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (full_name, email, password_hash, role_name)
                VALUES (?, ?, ?, 'staff')
            ");
            $stmt->execute([$full_name, $email, $password_hash]);

            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>สมัครสมาชิก</title>
  <style>
    body{font-family:Arial,sans-serif;background:#eef3f9;margin:0;padding:40px}
    .box{max-width:460px;margin:auto;background:#fff;padding:30px;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
    h1{margin-top:0;color:#1f4e98}
    input{width:100%;padding:12px;margin:8px 0 16px;border:1px solid #cfd8e3;border-radius:10px;box-sizing:border-box}
    button{background:#1f4e98;color:#fff;border:0;padding:12px 16px;border-radius:10px;cursor:pointer;font-weight:bold}
    a{color:#1f4e98;text-decoration:none}
    .msg{margin-bottom:16px;padding:12px;border-radius:10px;background:#fff3f3;color:#c0392b}
  </style>
</head>
<body>
  <div class="box">
    <h1>สมัครสมาชิก</h1>

    <?php if ($message !== ""): ?>
      <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="post">
      <label>ชื่อ - นามสกุล</label>
      <input type="text" name="full_name" required>

      <label>อีเมล</label>
      <input type="email" name="email" required>

      <label>รหัสผ่าน</label>
      <input type="password" name="password" required>

      <label>ยืนยันรหัสผ่าน</label>
      <input type="password" name="confirm_password" required>

      <button type="submit">สร้างบัญชี</button>
    </form>

    <p style="margin-top:16px;">
      มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a>
    </p>
  </div>
</body>
</html>
