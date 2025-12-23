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

$uid = (int)($_SESSION["user_id"] ?? 0);
if ($uid <= 0) { die("Not logged in"); }

$stmt = $conn->prepare("SELECT id,total,status,created_at FROM orders WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $uid);
$stmt->execute();
$orders = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>طلباتي - Emalen Salon</title>
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

    table{width:100%;border-collapse:collapse}
    th,td{padding:12px;border-bottom:1px solid rgba(0,0,0,.06);text-align:right;vertical-align:middle}
    th{color:#111;font-weight:900}

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

    .muted{color:#666;font-weight:800}
  </style>
</head>
<body>

  <div class="wrap">

    <div class="top">
      <div class="title">📦 طلباتي</div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a class="btn" href="shop.php">المتجر</a>
        <a class="btn" href="emalen.php">الرئيسية</a>
      </div>
    </div>

    <div class="card">
      <div class="muted" style="margin-bottom:10px">هون بتشوفي كل طلباتك وحالتها.</div>

      <div style="overflow:auto">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>التاريخ</th>
              <th>الإجمالي</th>
              <th>الحالة</th>
              <th>إجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($orders->num_rows===0): ?>
              <tr><td colspan="5">ما عندك طلبات لسه.</td></tr>
            <?php else: ?>
              <?php while($o=$orders->fetch_assoc()): ?>
                <?php
                  $st = (string)($o["status"] ?? "pending");
                  $pillClass = in_array($st, ["pending","paid","shipped","cancelled"], true) ? $st : "pending";
                ?>
                <tr>
                  <td><?= (int)$o["id"] ?></td>
                  <td><?= h($o["created_at"]) ?></td>
                  <td><?= number_format((float)$o["total"],2) ?> ₪</td>
                  <td><span class="pill <?= h($pillClass) ?>"><?= h($st) ?></span></td>
                  <td style="white-space:nowrap">
                    <?php if($st==="pending"): ?>
                      <a class="btn" href="pay.php?order_id=<?= (int)$o["id"] ?>">ادفعي</a>
                    <?php else: ?>
                      <a class="btn" href="order_details.php?order_id=<?= (int)$o["id"] ?>">تفاصيل</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</body>
</html>
