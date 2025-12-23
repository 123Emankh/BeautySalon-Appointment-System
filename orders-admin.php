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

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$nameCol = users_name_column($conn);

// تغيير حالة الطلب
if ($_SERVER["REQUEST_METHOD"]==="POST" && isset($_POST["order_id"], $_POST["new_status"])) {
  $oid=(int)$_POST["order_id"];
  $ns=$_POST["new_status"];
  $allowed=["pending","paid","shipped","cancelled"];
  if (in_array($ns,$allowed,true)) {
    $st=$conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $st->bind_param("si",$ns,$oid);
    $st->execute();
  }
  header("Location: orders-admin.php");
  exit();
}

$q = "
SELECT 
  o.id, o.total, o.status, o.created_at,
  u.id AS user_id, u.$nameCol AS customer_name, u.phone AS customer_phone
FROM orders o
JOIN users u ON u.id = o.user_id
ORDER BY o.created_at DESC
";
$orders = $conn->query($q);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>طلبات المتجر - Emalen Salon</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    *{margin:0;padding:0;box-sizing:border-box}

    body{
      font-family:'Cairo',sans-serif;
      color:#222;
      background:
        linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.45)),
        url("admin.jpg");
      background-size:cover;
      background-position:center;
      background-attachment:fixed;
    }

    .wrap{max-width:1100px;margin:32px auto;padding:0 16px}

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

    .card{
      background:rgba(255,255,255,.94);
      border-radius:18px;
      padding:18px;
      box-shadow:0 12px 35px rgba(0,0,0,.15);
      margin-bottom:18px;
      border:1px solid rgba(255,255,255,.35);
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

    /* Buttons: Black + Gold */
    .btn{
      background:#111;
      color:#d4a86a;
      border:1px solid #d4a86a;
      border-radius:999px;
      padding:8px 14px;
      cursor:pointer;
      font-weight:900;
      text-decoration:none;
      display:inline-block;
      transition:all .25s ease;
      white-space:nowrap;
    }
    .btn:hover{
      background:#d4a86a;
      color:#111;
    }

    /* Details button variant */
    .btn.info{
      background:#d4a86a;
      color:#111;
      border-color:#d4a86a;
    }
    .btn.info:hover{
      background:#111;
      color:#d4a86a;
    }

    /* Select styling */
    select{
      padding:8px 10px;
      border-radius:10px;
      border:1px solid #d7d7d7;
      font-family:'Cairo',sans-serif;
      font-weight:800;
      background:#fff;
    }
    select:focus{
      outline:none;
      border-color:#d4a86a;
      box-shadow:0 0 0 3px rgba(212,168,106,.22);
    }

    .muted{color:#666;font-weight:800}
  </style>
</head>

<body>
  <div class="wrap">

    <div class="top">
      <div class="title">📦 طلبات المتجر</div>
      <a class="btn" href="admin-dashboard.php">لوحة المدير</a>
    </div>

    <div class="card">
      <div style="overflow:auto">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>الزبون</th>
              <th>الجوال</th>
              <th>التاريخ</th>
              <th>الإجمالي</th>
              <th>الحالة</th>
              <th>تغيير</th>
            </tr>
          </thead>
          <tbody>
            <?php if($orders->num_rows===0): ?>
              <tr><td colspan="7">ما في طلبات لسه.</td></tr>
            <?php else: ?>
              <?php while($o=$orders->fetch_assoc()): ?>
                <tr>
                  <td><?= (int)$o["id"] ?></td>
                  <td><?= h($o["customer_name"]) ?></td>
                  <td><?= h($o["customer_phone"]) ?></td>
                  <td><?= h($o["created_at"]) ?></td>
                  <td><?= number_format((float)$o["total"],2) ?> ₪</td>
                  <td><?= h($o["status"]) ?></td>
                  <td>
                    <form method="POST" style="display:flex;gap:8px;flex-wrap:wrap;margin:0">
                      <input type="hidden" name="order_id" value="<?= (int)$o["id"] ?>">
                      <select name="new_status">
                        <?php foreach(["pending","paid","shipped","cancelled"] as $st): ?>
                          <option value="<?= h($st) ?>" <?= ($o["status"]===$st)?"selected":"" ?>><?= h($st) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button class="btn" type="submit">حفظ</button>
                      <a class="btn info" href="orders-details-admin.php?order_id=<?= (int)$o["id"] ?>">تفاصيل</a>
                    </form>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="muted" style="margin-top:10px">
        ملاحظة: حالات الطلب المسموحة: pending / paid / shipped / cancelled
      </div>
    </div>

  </div>
</body>
</html>
