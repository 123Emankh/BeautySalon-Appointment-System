<?php
global $conn;
require "db.php";

// تأكيد جداول المتجر
$conn->query("CREATE TABLE IF NOT EXISTS products (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, category VARCHAR(80) NULL, description TEXT NULL, price DECIMAL(10,2) NOT NULL, stock INT NOT NULL DEFAULT 0, image_url VARCHAR(255) NULL, active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS orders (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, total DECIMAL(10,2) NOT NULL DEFAULT 0, status ENUM('pending','paid','shipped','cancelled') NOT NULL DEFAULT 'pending', delivery_address VARCHAR(255) NULL, phone VARCHAR(30) NULL, notes VARCHAR(255) NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, product_id INT NOT NULL, qty INT NOT NULL, unit_price DECIMAL(10,2) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(order_id), INDEX(product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS payments (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, provider ENUM('demo','stripe') NOT NULL DEFAULT 'demo', provider_ref VARCHAR(255) NULL, amount DECIMAL(10,2) NOT NULL, currency VARCHAR(10) NOT NULL DEFAULT 'ils', status ENUM('initiated','succeeded','failed','cancelled') NOT NULL DEFAULT 'initiated', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(order_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

require_once "helpers.php";
session_start();

if (!isset($_SESSION["cart"]) || !is_array($_SESSION["cart"])) $_SESSION["cart"] = [];

$action = $_POST["action"] ?? $_GET["action"] ?? "";

if ($action === "add") {
  $pid = (int)($_POST["product_id"] ?? 0);
  if ($pid>0) {
    $_SESSION["cart"][$pid] = (int)($_SESSION["cart"][$pid] ?? 0) + 1;
  }
  header("Location: cart.php");
  exit();
}

if ($action === "remove") {
  $pid = (int)($_GET["product_id"] ?? 0);
  unset($_SESSION["cart"][$pid]);
  header("Location: cart.php");
  exit();
}

if ($action === "update" && $_SERVER["REQUEST_METHOD"]==="POST") {
  foreach(($_POST["qty"] ?? []) as $pid => $qty){
    $pid=(int)$pid; $qty=(int)$qty;
    if ($qty<=0) unset($_SESSION["cart"][$pid]);
    else $_SESSION["cart"][$pid]=$qty;
  }
  header("Location: cart.php");
  exit();
}

$cart = $_SESSION["cart"];
$items = [];
$total = 0;

if (count($cart)>0) {
  $ids = implode(",", array_map("intval", array_keys($cart)));
  $res = $conn->query("SELECT id,name,price,stock,image_url FROM products WHERE id IN ($ids) AND active=1");
  while($p=$res->fetch_assoc()){
    $pid=(int)$p["id"];
    $qty=(int)($cart[$pid] ?? 0);
    if ($qty<=0) continue;
    $line = $qty * (float)$p["price"];
    $total += $line;
    $items[] = ["p"=>$p,"qty"=>$qty,"line"=>$line];
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>السلة - Emalen Salon</title>
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

    .wrap{max-width:1000px;margin:32px auto;padding:0 16px}

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

    .btn.danger{
      background:#b00020;
      border-color:#b00020;
      color:#fff;
    }
    .btn.danger:hover{filter:brightness(.95)}

    .btn.primary{
      background:#d4a86a;
      border-color:#d4a86a;
      color:#111;
    }
    .btn.primary:hover{
      background:#111;
      color:#d4a86a;
    }

    .card{
      background:rgba(255,255,255,.95);
      border-radius:18px;
      padding:18px;
      box-shadow:0 12px 35px rgba(0,0,0,.15);
      margin-bottom:16px;
    }

    table{width:100%;border-collapse:collapse}
    th,td{
      padding:12px;
      border-bottom:1px solid rgba(0,0,0,.08);
      text-align:right;
      vertical-align:middle;
      font-weight:800;
    }
    th{color:#111;font-weight:900}

    .muted{color:#666;font-weight:800}

    input[type=number]{
      width:90px;
      padding:8px;
      border-radius:10px;
      border:1px solid #d7d7d7;
      font-family:'Cairo',sans-serif;
      font-weight:800;
    }
    input[type=number]:focus{
      outline:none;
      border-color:#d4a86a;
      box-shadow:0 0 0 3px rgba(212,168,106,.22);
    }
  </style>
</head>

<body>
  <div class="wrap">

    <div class="top">
      <div class="title">🧺 السلة</div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a class="btn" href="shop.php">رجوع للمتجر</a>
        <a class="btn" href="emalen.php">الرئيسية</a>
      </div>
    </div>

    <div class="card">
      <?php if (count($items)===0): ?>
        <div style="font-weight:900;color:#111">السلة فاضية. روحي على المتجر واختاري منتجات ✨</div>
      <?php else: ?>
        <form method="POST" action="cart.php">
          <input type="hidden" name="action" value="update">

          <div style="overflow:auto">
            <table>
              <thead>
                <tr>
                  <th>المنتج</th>
                  <th>السعر</th>
                  <th>الكمية</th>
                  <th>المجموع</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($items as $it): $p=$it["p"]; ?>
                  <tr>
                    <td>
                      <?= h($p["name"]) ?>
                      <div class="muted">المخزون: <?= (int)$p["stock"] ?></div>
                    </td>
                    <td><?= h($p["price"]) ?> ₪</td>
                    <td>
                      <input type="number" min="0" name="qty[<?= (int)$p["id"] ?>]" value="<?= (int)$it["qty"] ?>">
                    </td>
                    <td><?= number_format((float)$it["line"],2) ?> ₪</td>
                    <td>
                      <a class="btn danger" href="cart.php?action=remove&product_id=<?= (int)$p["id"] ?>"
                         onclick="return confirm('متأكدة بدك تحذفي المنتج من السلة؟');">
                        حذف
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:12px">
            <button class="btn" type="submit">تحديث السلة</button>
            <div style="font-weight:900;font-size:1.1rem;color:#111">
              الإجمالي: <?= number_format((float)$total,2) ?> ₪
            </div>
          </div>
        </form>

        <div style="margin-top:14px;text-align:left">
          <a class="btn primary" href="checkout.php">إكمال الطلب والدفع</a>
        </div>
      <?php endif; ?>
    </div>

  </div>
</body>
</html>
