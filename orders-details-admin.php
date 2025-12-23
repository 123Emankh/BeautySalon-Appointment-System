<?php
global $conn;
require "auth.php";
require_admin();
require "db.php";

// تأكيد جداول المتجر
$conn->query("CREATE TABLE IF NOT EXISTS products (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, category VARCHAR(80) NULL, description TEXT NULL, price DECIMAL(10,2) NOT NULL, stock INT NOT NULL DEFAULT 0, image_url VARCHAR(255) NULL, active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS orders (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, total DECIMAL(10,2) NOT NULL DEFAULT 0, status ENUM('pending','paid','shipped','cancelled') NOT NULL DEFAULT 'pending', delivery_address VARCHAR(255) NULL, phone VARCHAR(30) NULL, notes VARCHAR(255) NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, product_id INT NOT NULL, qty INT NOT NULL, unit_price DECIMAL(10,2) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(order_id), INDEX(product_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$conn->query("CREATE TABLE IF NOT EXISTS payments (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, provider ENUM('demo','stripe') NOT NULL DEFAULT 'demo', provider_ref VARCHAR(255) NULL, amount DECIMAL(10,2) NOT NULL, currency VARCHAR(10) NOT NULL DEFAULT 'ils', status ENUM('initiated','succeeded','failed','cancelled') NOT NULL DEFAULT 'initiated', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX(order_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

require_once "helpers.php";

$order_id=(int)($_GET["order_id"] ?? 0);
if ($order_id<=0) die("Invalid");

$nameCol = users_name_column($conn);

$stmt=$conn->prepare("
  SELECT o.*, u.$nameCol AS customer_name, u.email, u.phone AS customer_phone
  FROM orders o
  JOIN users u ON u.id=o.user_id
  WHERE o.id=?
");
$stmt->bind_param("i",$order_id);
$stmt->execute();
$order=$stmt->get_result()->fetch_assoc();
if(!$order) die("Not found");

$item=$conn->prepare("
  SELECT oi.qty, oi.unit_price, p.name
  FROM order_items oi
  JOIN products p ON p.id=oi.product_id
  WHERE oi.order_id=?
");
$item->bind_param("i",$order_id);
$item->execute();
$items=$item->get_result();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>تفاصيل طلب - المدير</title>
  <link rel="stylesheet" href="style.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    body{background:#fdf8f5}
    .wrap{max-width:900px;margin:32px auto;padding:0 16px}
    .card{background:#fff;border-radius:18px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,0.08);margin-bottom:16px}
    table{width:100%;border-collapse:collapse}
    th,td{padding:12px;border-bottom:1px solid #eee;text-align:right}
    .btn{background:#6d3a75;color:#fff;border:none;border-radius:999px;padding:10px 18px;cursor:pointer;font-weight:800;text-decoration:none;display:inline-block}
    .btn:hover{background:#4b2c4f}
  </style>
</head>
<body>
  <div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
      <div style="font-weight:900;color:#4b2c4f;font-size:1.3rem">🧾 تفاصيل طلب #<?= (int)$order_id ?></div>
      <a class="btn" href="orders-admin.php">رجوع</a>
    </div>

    <div class="card">
      <div><b>الزبون:</b> <?= h($order["customer_name"]) ?></div>
      <div><b>الإيميل:</b> <?= h($order["email"]) ?></div>
      <div><b>الجوال:</b> <?= h($order["customer_phone"]) ?></div>
      <div><b>العنوان:</b> <?= h($order["delivery_address"]) ?></div>
      <div><b>الإجمالي:</b> <?= number_format((float)$order["total"],2) ?> ₪</div>
      <div><b>الحالة:</b> <?= h($order["status"]) ?></div>
      <div><b>ملاحظات:</b> <?= h($order["notes"]) ?></div>
      <div><b>تاريخ:</b> <?= h($order["created_at"]) ?></div>
    </div>

    <div class="card">
      <div style="font-weight:900;margin-bottom:10px">المنتجات</div>
      <div style="overflow:auto">
        <table>
          <thead><tr><th>المنتج</th><th>الكمية</th><th>سعر الوحدة</th></tr></thead>
          <tbody>
            <?php while($i=$items->fetch_assoc()): ?>
              <tr>
                <td><?= h($i["name"]) ?></td>
                <td><?= (int)$i["qty"] ?></td>
                <td><?= number_format((float)$i["unit_price"],2) ?> ₪</td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
