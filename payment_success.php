<?php
global $conn;
require "auth.php";
require_login();
require "db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// تأكيد جداول المتجر
$conn->query("CREATE TABLE IF NOT EXISTS products (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, category VARCHAR(80) NULL, description TEXT NULL, price DECIMAL(10,2) NOT NULL, stock INT NOT NULL DEFAULT 0, image_url VARCHAR(255) NULL, active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS orders (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, total DECIMAL(10,2) NOT NULL DEFAULT 0, status ENUM('pending','paid','shipped','cancelled') NOT NULL DEFAULT 'pending', delivery_address VARCHAR(255) NULL, phone VARCHAR(30) NULL, notes VARCHAR(255) NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, product_id INT NOT NULL, qty INT NOT NULL, unit_price DECIMAL(10,2) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(order_id), INDEX(product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS payments (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, provider ENUM('demo','stripe') NOT NULL DEFAULT 'demo', provider_ref VARCHAR(255) NULL, amount DECIMAL(10,2) NOT NULL, currency VARCHAR(10) NOT NULL DEFAULT 'ils', status ENUM('initiated','succeeded','failed','cancelled') NOT NULL DEFAULT 'initiated', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(order_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

require "payment_config.php";
require_once "helpers.php";

// غالباً auth.php عامل session_start، فبنحمي حالنا:
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$order_id = (int)($_GET["order_id"] ?? 0);
$uid = (int)($_SESSION["user_id"] ?? 0);

if ($order_id <= 0 || $uid <= 0) { die("Invalid"); }

$stmt = $conn->prepare("SELECT id,total,status FROM orders WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $order_id, $uid);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) die("Order not found");

function stripe_get($url){
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer ".STRIPE_SECRET_KEY]);
  $resp = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  if ($resp === false) {
    $err = curl_error($ch);
    curl_close($ch);
    throw new Exception($err);
  }

  curl_close($ch);
  $json = json_decode($resp, true);

  if ($code < 200 || $code >= 300) {
    $msg = $json["error"]["message"] ?? $resp;
    throw new Exception($msg);
  }

  return $json;
}

$note = "";

// Stripe verify only if stripe mode + session_id + keys
if (PAYMENT_MODE==="stripe" && isset($_GET["session_id"]) && STRIPE_SECRET_KEY!=="") {
  try{
    $sid  = (string)$_GET["session_id"];
    $sess = stripe_get("https://api.stripe.com/v1/checkout/sessions/".urlencode($sid));
    $paid = (($sess["payment_status"] ?? "") === "paid");

    if ($paid) {
      $conn->begin_transaction();

      $up1 = $conn->prepare("UPDATE orders SET status='paid' WHERE id=? AND user_id=?");
      $up1->bind_param("ii", $order_id, $uid);
      $up1->execute();

      $up2 = $conn->prepare("UPDATE payments SET provider='stripe', provider_ref=?, status='succeeded' WHERE order_id=?");
      $up2->bind_param("si", $sid, $order_id);
      $up2->execute();

      $conn->commit();
      $note = "تم الدفع بنجاح عبر Stripe ✅";
    } else {
      $note = "الدفع لسه مش مؤكد. إذا دفعتي، ارجعي لطلباتي وتحققي.";
    }
  } catch(Exception $e){
    $note = "ما قدرت أتحقق من Stripe: ".$e->getMessage();
  }
} else {
  // demo أو نجاح مباشر
  if (($order["status"] ?? "") === "paid") $note = "تم الدفع ✅";
  else $note = "تم إنشاء الطلب ✅ (إذا الدفع Demo ارجعي لصفحة الدفع لإتمامه).";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>نجاح الدفع - Emalen Salon</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    *{margin:0;padding:0;box-sizing:border-box}

    body{
      font-family:'Cairo',sans-serif;
      color:#222;
      background:
        linear-gradient(rgba(0,0,0,.22), rgba(0,0,0,.22)),
        url("shop.jpg");
      background-size:cover;
      background-position:center;
      background-attachment:fixed;
    }

    .wrap{max-width:700px;margin:40px auto;padding:0 16px}

    .card{
      background:rgba(255,255,255,.95);
      border-radius:18px;
      padding:22px;
      box-shadow:0 12px 35px rgba(0,0,0,.15);
    }

    .title{
      font-weight:900;
      color:#111;
      font-size:1.45rem;
      margin-bottom:12px;
    }

    .ok{
      background:rgba(212,168,106,.25);
      color:#111;
      padding:12px;
      border-radius:12px;
      font-weight:900;
      line-height:1.7;
      border:1px solid rgba(212,168,106,.35);
    }

    .meta{
      margin-top:10px;
      font-weight:900;
      color:#111;
    }

    .btn{
      background:#111;
      color:#d4a86a;
      border:1px solid #d4a86a;
      border-radius:999px;
      padding:10px 18px;
      cursor:pointer;
      font-weight:900;
      text-decoration:none;
      display:inline-block;
      transition:.25s;
      white-space:nowrap;
    }
    .btn:hover{
      background:#d4a86a;
      color:#111;
    }
  </style>
</head>

<body>
  <div class="wrap">
    <div class="card">
      <div class="title">✅ نجاح العملية</div>

      <div class="ok"><?= h($note) ?></div>

      <div class="meta">طلبك رقم: #<?= (int)$order_id ?> — الإجمالي: <?= number_format((float)$order["total"],2) ?> ₪</div>

      <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap">
        <a class="btn" href="my-orders.php">طلباتي</a>
        <a class="btn" href="shop.php">رجوع للمتجر</a>
        <a class="btn" href="emalen.php">الرئيسية</a>
      </div>
    </div>
  </div>
</body>
</html>
