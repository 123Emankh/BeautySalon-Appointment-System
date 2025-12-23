<?php
global $conn;
require "auth.php";
require "db.php";

// تأكيد جداول المتجر
$conn->query("CREATE TABLE IF NOT EXISTS products (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, category VARCHAR(80) NULL, description TEXT NULL, price DECIMAL(10,2) NOT NULL, stock INT NOT NULL DEFAULT 0, image_url VARCHAR(255) NULL, active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS orders (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, total DECIMAL(10,2) NOT NULL DEFAULT 0, status ENUM('pending','paid','shipped','cancelled') NOT NULL DEFAULT 'pending', delivery_address VARCHAR(255) NULL, phone VARCHAR(30) NULL, notes VARCHAR(255) NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, product_id INT NOT NULL, qty INT NOT NULL, unit_price DECIMAL(10,2) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(order_id), INDEX(product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS payments (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, provider ENUM('demo','stripe') NOT NULL DEFAULT 'demo', provider_ref VARCHAR(255) NULL, amount DECIMAL(10,2) NOT NULL, currency VARCHAR(10) NOT NULL DEFAULT 'ils', status ENUM('initiated','succeeded','failed','cancelled') NOT NULL DEFAULT 'initiated', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(order_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

require_once "helpers.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$cart_count = 0;
if (isset($_SESSION["cart"]) && is_array($_SESSION["cart"])) {
  foreach($_SESSION["cart"] as $q) $cart_count += (int)$q;
}

$products = $conn->query("SELECT id,name,description,price,stock,image_url FROM products WHERE active=1 ORDER BY created_at DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>المتجر - Emalen Salon</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    *{margin:0;padding:0;box-sizing:border-box}

    body{
      font-family:'Cairo',sans-serif;
      color:#222;
      background:
     
  linear-gradient(rgba(0,0,0,.25), rgba(0,0,0,.25)),
  url("shop.jpg");

      background-size:cover;
      background-position:center;
      background-attachment:fixed;
    }

    .wrap{max-width:1100px;margin:32px auto;padding:0 16px}

    /* Top bar */
    .top{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      flex-wrap:wrap;
      margin-bottom:18px;
    }

    .title{
      font-weight:900;
      font-size:1.4rem;
      color:#fff;
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

    /* Products grid */
    .grid{
      display:grid;
      grid-template-columns:repeat(3,1fr);
      gap:16px;
    }

    .card{
      background:rgba(255,255,255,.94);
      border-radius:18px;
      box-shadow:0 12px 35px rgba(0,0,0,.15);
      overflow:hidden;
      display:flex;
      flex-direction:column;
    }

    .img{
      height:170px;
      background:#eee;
    }
    .img img{
      width:100%;
      height:100%;
      object-fit:cover;
    }

    .content{
      padding:14px;
      display:flex;
      flex-direction:column;
      gap:10px;
      flex:1;
    }

    .name{
      font-weight:900;
      color:#111;
      margin:0;
    }

    .desc{
      color:#555;
      font-size:.95rem;
      min-height:42px;
    }

    .row{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      margin-top:auto;
    }

    .price{
      font-weight:900;
      font-size:1.05rem;
    }

    .stock{
      font-size:.85rem;
      color:#777;
    }

    @media(max-width:900px){
      .grid{grid-template-columns:repeat(2,1fr)}
    }
    @media(max-width:520px){
      .grid{grid-template-columns:1fr}
    }
  </style>
</head>
<body>

<div class="wrap">

  <div class="top">
    <div class="title">🛍️ متجر إمالين</div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a class="btn" href="emalen.php">الرئيسية</a>
      <a class="btn" href="cart.php">السلة (<?= (int)$cart_count ?>)</a>
    </div>
  </div>

  <div class="grid">
    <?php if ($products->num_rows===0): ?>
      <div class="card" style="grid-column:1/-1;padding:18px;font-weight:900">
        ما في منتجات مفعّلة لسه.
      </div>
    <?php else: ?>
      <?php while($p=$products->fetch_assoc()): ?>
        <div class="card">
          <div class="img">
            <?php if (!empty($p["image_url"])): ?>
              <img src="<?= h($p["image_url"]) ?>" alt="<?= h($p["name"]) ?>">
            <?php endif; ?>
          </div>
          <div class="content">
            <h3 class="name"><?= h($p["name"]) ?></h3>
            <p class="desc"><?= h(mb_strimwidth($p["description"] ?? "", 0, 120, "...", "UTF-8")) ?></p>

            <div class="row">
              <div>
                <div class="price"><?= h($p["price"]) ?> ₪</div>
                <div class="stock">المخزون: <?= (int)$p["stock"] ?></div>
              </div>
              <form method="POST" action="cart.php" style="margin:0">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int)$p["id"] ?>">
                <button class="btn" type="submit"
                  <?= ((int)$p["stock"]<=0) ? "disabled style='opacity:.6;cursor:not-allowed'" : "" ?>>
                  <?= ((int)$p["stock"]<=0) ? "نفدت" : "أضيفي للسلة" ?>
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>

</div>

</body>
</html>
