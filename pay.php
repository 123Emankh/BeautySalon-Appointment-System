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

/*
  ملاحظة:
  غالباً auth.php عامل session_start()
  فلو بدك تأمني حالك بدون Notice:
*/
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$order_id = (int)($_GET["order_id"] ?? 0);
if ($order_id <= 0) { die("Order not found"); }

$uid = (int)($_SESSION["user_id"] ?? 0);
if ($uid <= 0) { die("Not logged in"); }

$stmt = $conn->prepare("SELECT id,total,status FROM orders WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $order_id, $uid);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) { die("Order not found"); }
if ($order["status"] !== "pending") { header("Location: my-orders.php"); exit(); }

$error = "";

function stripe_post($url, $data){
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer ".STRIPE_SECRET_KEY
  ]);

  $resp = curl_exec($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  if ($resp === false) {
    $err = curl_error($ch);
    curl_close($ch);
    throw new Exception("Stripe connection failed: ".$err);
  }

  curl_close($ch);
  $json = json_decode($resp, true);

  if ($code < 200 || $code >= 300) {
    $msg = $json["error"]["message"] ?? $resp;
    throw new Exception("Stripe error: ".$msg);
  }

  return $json;
}

if (PAYMENT_MODE === "stripe") {
  if (STRIPE_SECRET_KEY === "") {
    $error = "Stripe mode مفعّل بس ما حطيتي المفاتيح داخل payment_config.php";
  } else {
    $base = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]==="on" ? "https" : "http")
          . "://".$_SERVER["HTTP_HOST"].dirname($_SERVER["REQUEST_URI"]);
    $success_url = $base."/payment_success.php?order_id=".$order_id."&session_id={CHECKOUT_SESSION_ID}";
    $cancel_url  = $base."/payment_cancel.php?order_id=".$order_id;

    try{
      $session = stripe_post("https://api.stripe.com/v1/checkout/sessions", [
        "mode" => "payment",
        "success_url" => $success_url,
        "cancel_url"  => $cancel_url,

        "line_items[0][quantity]" => 1,
        "line_items[0][price_data][currency]" => CURRENCY,
        "line_items[0][price_data][product_data][name]" => "Emalen Order #".$order_id,
        "line_items[0][price_data][unit_amount]" => (int)round(((float)$order["total"])*100),
      ]);

      $up = $conn->prepare("UPDATE payments SET provider='stripe', provider_ref=? WHERE order_id=?");
      $sid = $session["id"];
      $up->bind_param("si", $sid, $order_id);
      $up->execute();

      header("Location: ".$session["url"]);
      exit();
    } catch(Exception $e){
      $error = $e->getMessage();
    }
  }
}

if ($_SERVER["REQUEST_METHOD"]==="POST" && ($_POST["pay_demo"] ?? "")==="1" && PAYMENT_MODE!=="stripe") {
  $conn->begin_transaction();
  try{
    $up1 = $conn->prepare("UPDATE orders SET status='paid' WHERE id=? AND user_id=?");
    $up1->bind_param("ii", $order_id, $uid);
    $up1->execute();

    $up2 = $conn->prepare("UPDATE payments SET provider='demo', provider_ref=?, status='succeeded' WHERE order_id=?");
    $ref = "DEMO-".time();
    $up2->bind_param("si", $ref, $order_id);
    $up2->execute();

    $conn->commit();
    header("Location: payment_success.php?order_id=".$order_id);
    exit();
  } catch(Exception $e){
    $conn->rollback();
    $error = "صار خطأ بالدفع التجريبي: ".$e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>الدفع - Emalen Salon</title>
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

    .wrap{max-width:800px;margin:32px auto;padding:0 16px}

    .top{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
      margin-bottom:14px;
    }

    .title{
      font-weight:900;
      color:#fff;
      font-size:1.3rem;
      text-shadow:0 6px 18px rgba(0,0,0,.35);
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

    .card{
      background:rgba(255,255,255,.95);
      border-radius:18px;
      padding:18px;
      box-shadow:0 12px 35px rgba(0,0,0,.15);
      margin-bottom:16px;
    }

    .msg{
      padding:12px;
      border-radius:12px;
      font-weight:900;
      margin:10px 0;
    }
    .err{background:rgba(176,0,32,.15);color:#5a0011}
    .ok{background:rgba(212,168,106,.25);color:#111}

    .muted{color:#666;font-weight:800;margin-top:8px}
  </style>
</head>
<body>

  <div class="wrap">

    <div class="top">
      <div class="title">💳 الدفع</div>
      <a class="btn" href="my-orders.php">طلباتي</a>
    </div>

    <div class="card">
      <div style="font-weight:900;color:#111">
        طلب رقم #<?= (int)$order_id ?> — الإجمالي: <?= number_format((float)$order["total"],2) ?> ₪
      </div>

      <?php if($error!==""): ?>
        <div class="msg err"><?= h($error) ?></div>
      <?php endif; ?>

      <?php if(PAYMENT_MODE==="stripe"): ?>
        <div class="msg ok">إذا كل شي تمام، رح يتم تحويلك تلقائيًا لصفحة Stripe للدفع.</div>
        <div class="muted">إذا ضلّك هون، غالبًا في مشكلة بالمفاتيح أو الاتصال.</div>
      <?php else: ?>
        <div class="muted">
         
        </div>
        <form method="POST" style="margin-top:12px">
          <input type="hidden" name="pay_demo" value="1">
          <button class="btn" type="submit">ادفعي الآن (Demo)</button>
        </form>
      <?php endif; ?>
    </div>

  </div>

</body>
</html>
