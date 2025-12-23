<?php
global $conn;
require "auth.php";
require_login();
require "db.php";

// تأكيد جداول المتجر
$conn->query("CREATE TABLE IF NOT EXISTS products (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, category VARCHAR(80) NULL, description TEXT NULL, price DECIMAL(10,2) NOT NULL, stock INT NOT NULL DEFAULT 0, image_url VARCHAR(255) NULL, active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS orders (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, total DECIMAL(10,2) NOT NULL DEFAULT 0, status ENUM('pending','paid','shipped','cancelled') NOT NULL DEFAULT 'pending', delivery_address VARCHAR(255) NULL, phone VARCHAR(30) NULL, notes VARCHAR(255) NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, product_id INT NOT NULL, qty INT NOT NULL, unit_price DECIMAL(10,2) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(order_id), INDEX(product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS payments (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, provider ENUM('demo','stripe') NOT NULL DEFAULT 'demo', provider_ref VARCHAR(255) NULL, amount DECIMAL(10,2) NOT NULL, currency VARCHAR(10) NOT NULL DEFAULT 'ils', status ENUM('initiated','succeeded','failed','cancelled') NOT NULL DEFAULT 'initiated', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(order_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

require_once "helpers.php";


if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"]) || count($_SESSION["cart"])===0) {
  header("Location: cart.php");
  exit();
}

$error="";
$cart=$_SESSION["cart"];

function load_cart_products($conn, $cart){
  $ids = implode(",", array_map("intval", array_keys($cart)));
  $res = $conn->query("SELECT id,name,price,stock FROM products WHERE id IN ($ids) AND active=1");
  $products=[];
  while($p=$res->fetch_assoc()){ $products[(int)$p["id"]]=$p; }
  return $products;
}

$products = load_cart_products($conn,$cart);

$total=0;
$lines=[];
foreach($cart as $pid=>$qty){
  $pid=(int)$pid; $qty=(int)$qty;
  if ($qty<=0) continue;
  if (!isset($products[$pid])) { $error="في منتج بالسلة مش موجود/مش مفعّل."; break; }
  $p=$products[$pid];
  if ($qty > (int)$p["stock"]) { $error="الكمية المطلوبة أكبر من المخزون لمنتج: ".$p["name"]; break; }
  $line = $qty * (float)$p["price"];
  $total += $line;
  $lines[]=["pid"=>$pid,"qty"=>$qty,"price"=>$p["price"]];
}

if ($_SERVER["REQUEST_METHOD"]==="POST" && $error==="") {
  $address = trim($_POST["delivery_address"] ?? "");
  $phone   = trim($_POST["phone"] ?? "");
  $notes   = trim($_POST["notes"] ?? "");

  if ($address==="" || $phone==="") {
    $error="العنوان ورقم الجوال مطلوبين.";
  } else {
    $conn->begin_transaction();
    try{
      $stmt=$conn->prepare("INSERT INTO orders (user_id,total,status,delivery_address,phone,notes) VALUES (?,?,?,?,?,?)");
      $uid=(int)$_SESSION["user_id"];
      $status="pending";
      $stmt->bind_param("idssss",$uid,$total,$status,$address,$phone,$notes);
      $stmt->execute();
      $order_id = $stmt->insert_id;

      $itemStmt=$conn->prepare("INSERT INTO order_items (order_id,product_id,qty,unit_price) VALUES (?,?,?,?)");
      $stockStmt=$conn->prepare("UPDATE products SET stock = stock - ? WHERE id=? AND stock >= ?");

      foreach($lines as $l){
        $pid=$l["pid"]; $qty=$l["qty"]; $price=(float)$l["price"];
        $itemStmt->bind_param("iiid",$order_id,$pid,$qty,$price);
        $itemStmt->execute();

        $stockStmt->bind_param("iii",$qty,$pid,$qty);
        $stockStmt->execute();
        if ($stockStmt->affected_rows===0) throw new Exception("المخزون تغيّر، جرّبي مرة ثانية.");
      }

      $payStmt=$conn->prepare("INSERT INTO payments (order_id,provider,amount,currency,status) VALUES (?,?,?,?,?)");
      $provider="demo";
      $currency="ils";
      $pstatus="initiated";
      $payStmt->bind_param("isdss",$order_id,$provider,$total,$currency,$pstatus);
      $payStmt->execute();

      $conn->commit();

      $_SESSION["cart"]=[];
      header("Location: pay.php?order_id=".$order_id);
      exit();
    }catch(Exception $e){
      $conn->rollback();
      $error = "صار خطأ: ".$e->getMessage();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>إكمال الطلب - Emalen Salon</title>
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

    .row{display:grid;grid-template-columns:1fr;gap:12px}

    label{font-weight:900;color:#111}

    input,textarea{
      width:100%;
      padding:12px;
      border-radius:12px;
      border:1px solid #d7d7d7;
      font-family:'Cairo',sans-serif;
      font-weight:800;
    }

    input:focus,textarea:focus{
      outline:none;
      border-color:#d4a86a;
      box-shadow:0 0 0 3px rgba(212,168,106,.22);
    }

    .muted{color:#666;font-weight:800;margin-top:6px}
  </style>
</head>

<body>
  <div class="wrap">

    <div class="top">
      <div class="title">🧾 إكمال الطلب</div>
      <a class="btn" href="shop.php">رجوع للمتجر</a>
    </div>

    <div class="card">
      <div style="font-weight:900;font-size:1.1rem;color:#111">
        الإجمالي: <?= number_format((float)$total,2) ?> ₪
      </div>
      <div class="muted">الدفع أونلاين بالخطوة الجاي.</div>

      <?php if($error!==""): ?>
        <div class="msg err"><?= h($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="row">
        <div>
          <label>عنوان التوصيل</label>
          <input name="delivery_address" required placeholder="مثال: نابلس - رفيديا - شارع ...">
        </div>

        <div>
          <label>رقم الجوال</label>
          <input name="phone" required placeholder="05xxxxxxxx">
        </div>

        <div>
          <label>ملاحظات (اختياري)</label>
          <textarea name="notes" rows="3" placeholder="أي ملاحظة للطلب"></textarea>
        </div>

        <button class="btn" type="submit">متابعة للدفع</button>
      </form>
    </div>

  </div>
</body>
</html>
