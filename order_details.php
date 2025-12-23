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

require_once "helpers.php";

// auth.php غالباً عامل session_start، فبنحمي حالنا:
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$order_id = (int)($_GET["order_id"] ?? 0);
$uid = (int)($_SESSION["user_id"] ?? 0);

if ($order_id <= 0 || $uid <= 0) { die("Invalid"); }

$stmt = $conn->prepare("SELECT id,total,status,delivery_address,phone,notes,created_at FROM orders WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $order_id, $uid);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) die("Order not found");

$item = $conn->prepare("
  SELECT oi.qty, oi.unit_price, p.name
  FROM order_items oi
  JOIN products p ON p.id = oi.product_id
  WHERE oi.order_id = ?
");
$item->bind_param("i", $order_id);
$item->execute();
$items = $item->get_result();

$status = (string)($order["status"] ?? "pending");
$allowed = ["pending","paid","shipped","cancelled"];
$stClass = in_array($status, $allowed, true) ? $status : "pending";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>تفاصيل الطلب - Emalen Salon</title>
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

    .wrap{max-width:900px;margin:32px auto;padding:0 16px}

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

    .meta{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:12px;
      margin-top:10px;
    }
    .meta .item{
      background:rgba(0,0,0,.03);
      border:1px solid rgba(0,0,0,.06);
      border-radius:14px;
      padding:12px;
    }
    .label{color:#666;font-weight:900;margin-bottom:4px}
    .val{font-weight:900;color:#111;line-height:1.6}

    table{width:100%;border-collapse:collapse}
    th,td{padding:12px;border-bottom:1px solid rgba(0,0,0,.06);text-align:right;vertical-align:middle}
    th{font-weight:900;color:#111}

    .pill{
      display:inline-block;
      padding:6px 12px;
      border-radius:999px;
      font-weight:900;
      font-size:.9rem;
      border:1px solid rgba(0,0,0,.08);
      white-space:nowrap;
    }

    .pill.pending{background:rgba(212,168,106,.22);color:#111;border-color:rgba(212,168,106,.35)}
    .pill.paid{background:rgba(20,120,70,.12);color:#0d3b24;border-color:rgba(20,120,70,.22)}
    .pill.shipped{background:rgba(20,90,160,.12);color:#07305a;border-color:rgba(20,90,160,.22)}
    .pill.cancelled{background:rgba(176,0,32,.14);color:#5a0011;border-color:rgba(176,0,32,.22)}

    @media (max-width:700px){
      .meta{grid-template-columns:1fr}
    }
  </style>
</head>

<body>
  <div class="wrap">

    <div class="top">
      <div class="title">🧾 تفاصيل الطلب #<?= (int)$order_id ?></div>
      <a class="btn" href="my-orders.php">رجوع</a>
    </div>

    <div class="card">
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:space-between">
        <div style="font-weight:900;color:#111">
          حالة الطلب:
          <span class="pill <?= h($stClass) ?>"><?= h($status) ?></span>
        </div>
        <div style="font-weight:900;color:#111">
          الإجمالي: <?= number_format((float)$order["total"],2) ?> ₪
        </div>
      </div>

      <div class="meta">
        <div class="item">
          <div class="label">العنوان</div>
          <div class="val"><?= h($order["delivery_address"] ?? "-") ?></div>
        </div>
        <div class="item">
          <div class="label">الجوال</div>
          <div class="val"><?= h($order["phone"] ?? "-") ?></div>
        </div>
        <div class="item">
          <div class="label">ملاحظات</div>
          <div class="val"><?= h($order["notes"] ?? "-") ?></div>
        </div>
        <div class="item">
          <div class="label">تاريخ الطلب</div>
          <div class="val"><?= h($order["created_at"] ?? "-") ?></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div style="font-weight:900;margin-bottom:10px;color:#111">المنتجات</div>
      <div style="overflow:auto">
        <table>
          <thead>
            <tr>
              <th>المنتج</th>
              <th>الكمية</th>
              <th>سعر الوحدة</th>
              <th>المجموع</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $grand = 0;
              while($i = $items->fetch_assoc()):
                $qty = (int)$i["qty"];
                $unit = (float)$i["unit_price"];
                $line = $qty * $unit;
                $grand += $line;
            ?>
              <tr>
                <td><?= h($i["name"]) ?></td>
                <td><?= $qty ?></td>
                <td><?= number_format($unit,2) ?> ₪</td>
                <td><?= number_format($line,2) ?> ₪</td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <div style="margin-top:12px;font-weight:900;color:#111">
        مجموع المنتجات: <?= number_format((float)$grand,2) ?> ₪
      </div>
    </div>

  </div>
</body>
</html>
