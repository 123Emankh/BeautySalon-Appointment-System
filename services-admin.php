<?php
global $conn;
require "auth.php";
require_admin();
require "db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// تأكيد وجود جدول الخدمات
$conn->query("
  CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(80) NOT NULL,
    duration_min INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$msg = "";
$err = "";

// حذف
if (isset($_GET["delete"])) {
  $id = (int)$_GET["delete"];
  try {
    $stmt = $conn->prepare("DELETE FROM services WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $msg = "تم حذف الخدمة ✅";
  } catch (mysqli_sql_exception $e) {
    $err = "ما بزبط نحذفها لأن عليها حجوزات. عطّليها بدل الحذف (Active = 0).";
  }
}

// تفعيل/تعطيل
if (isset($_GET["toggle"])) {
  $id = (int)$_GET["toggle"];
  $stmt = $conn->prepare("UPDATE services SET active = IF(active=1,0,1) WHERE id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $msg = "تم تحديث حالة الخدمة ✅";
}

// إضافة / تعديل
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $action = $_POST["action"] ?? "";
  $name = trim($_POST["name"] ?? "");
  $category = trim($_POST["category"] ?? "");
  $duration = (int)($_POST["duration_min"] ?? 0);
  $price = (float)($_POST["price"] ?? 0);

  if ($name === "" || $category === "" || $duration <= 0 || $price <= 0) {
    $err = "عبّي كل الحقول صح (المدة والسعر لازم > 0).";
  } else {
    if ($action === "add") {
      $stmt = $conn->prepare("INSERT INTO services (name, category, duration_min, price, active) VALUES (?, ?, ?, ?, 1)");
      $stmt->bind_param("ssid", $name, $category, $duration, $price);
      $stmt->execute();
      $msg = "تمت إضافة الخدمة ✅";
    } elseif ($action === "edit") {
      $id = (int)($_POST["id"] ?? 0);
      $stmt = $conn->prepare("UPDATE services SET name=?, category=?, duration_min=?, price=? WHERE id=?");
      $stmt->bind_param("ssidi", $name, $category, $duration, $price, $id);
      $stmt->execute();
      $msg = "تم تعديل الخدمة ✅";
    }
  }
}

// تجهيز بيانات التعديل
$editRow = null;
if (isset($_GET["edit"])) {
  $id = (int)$_GET["edit"];
  $stmt = $conn->prepare("SELECT * FROM services WHERE id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $editRow = $stmt->get_result()->fetch_assoc();
  if (!$editRow) $err = "الخدمة غير موجودة.";
}

// جلب كل الخدمات
$rows = $conn->query("SELECT * FROM services ORDER BY category, name");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>إدارة الخدمات - Emalen Salon</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    *{margin:0;padding:0;box-sizing:border-box}

    body{
      font-family:'Cairo',sans-serif;
      color:#222;
      margin:0;
      background:
        linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
        url("admin.jpg");
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
    }

    .wrap{max-width:1200px;margin:30px auto;padding:0 16px}

    .box{
      background:rgba(255,255,255,0.94);
      border-radius:18px;
      padding:18px;
      box-shadow:0 12px 35px rgba(0,0,0,0.15);
      margin-bottom:18px;
      border:1px solid rgba(255,255,255,0.35);
    }

    h2{margin:0 0 10px;color:#111}
    .muted{color:#666;font-weight:800}

    .row{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px}
    .row-1{display:grid;grid-template-columns:1fr;gap:12px}

    label{font-weight:900;color:#333;font-size:.95rem}

    input,select{
      width:100%;
      padding:12px;
      border:1px solid #d7d7d7;
      border-radius:12px;
      font-family:'Cairo',sans-serif;
      background:#fff;
      outline:none;
    }
    input:focus,select:focus{
      border-color:#d4a86a;
      box-shadow:0 0 0 3px rgba(212,168,106,0.22);
    }

    /* Buttons: Black + Gold */
    .btn{
      display:inline-block;
      background:#111;
      color:#d4a86a;
      border:1px solid #d4a86a;
      border-radius:999px;
      padding:10px 16px;
      cursor:pointer;
      font-weight:900;
      text-decoration:none;
      transition:all .25s ease;
      white-space:nowrap;
    }
    .btn:hover{
      background:#d4a86a;
      color:#111;
    }

    .btn2{
      background:#d4a86a;
      color:#111;
      border:1px solid #d4a86a;
    }
    .btn2:hover{
      background:#111;
      color:#d4a86a;
    }

    .danger{
      background:#b00020;
      color:#fff;
      border:1px solid #b00020;
    }
    .danger:hover{filter:brightness(.95)}

    table{width:100%;border-collapse:collapse;background:transparent}
    th,td{
      padding:12px;
      border-bottom:1px solid rgba(0,0,0,0.08);
      text-align:right;
      vertical-align:top
    }
    th{color:#111;font-weight:900}
    td{color:#222;font-weight:800}

    .pill{
      display:inline-block;
      padding:6px 12px;
      border-radius:999px;
      font-weight:900;
      font-size:.9rem
    }
    .on{background:rgba(212,168,106,0.22);color:#111;border:1px solid rgba(212,168,106,0.5)}
    .off{background:rgba(0,0,0,0.08);color:#111;border:1px solid rgba(0,0,0,0.12)}

    .msg{padding:12px;border-radius:12px;font-weight:900;margin:10px 0}
    .ok{background:rgba(212,168,106,0.22);color:#111;border:1px solid rgba(212,168,106,0.45)}
    .err{background:rgba(176,0,32,0.14);color:#5a0011;border:1px solid rgba(176,0,32,0.25)}

    .top-actions{
      display:flex;
      gap:10px;
      justify-content:space-between;
      align-items:center;
      flex-wrap:wrap
    }

    @media (max-width: 900px){
      .row{grid-template-columns:1fr}
    }
  </style>
</head>
<body>

<div class="wrap">

  <div class="box top-actions">
    <div>
      <h2>إدارة الخدمات</h2>
      <div class="muted">إضافة / تعديل / حذف / تفعيل-تعطيل</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a class="btn" href="admin-dashboard.php">لوحة الأدمن</a>
      <a class="btn" href="bookings-admin.php">الحجوزات</a>
      <a class="btn btn2" href="logout.php">تسجيل خروج</a>
    </div>
  </div>

  <?php if ($msg !== ""): ?>
    <div class="box msg ok"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <?php if ($err !== ""): ?>
    <div class="box msg err"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <div class="box">
    <?php if ($editRow): ?>
      <h2>تعديل خدمة</h2>
      <form method="POST" class="row-1">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" value="<?= (int)$editRow["id"] ?>">

        <div class="row">
          <div>
            <label>اسم الخدمة</label>
            <input name="name" value="<?= htmlspecialchars($editRow["name"]) ?>" required>
          </div>
          <div>
            <label>التصنيف</label>
            <input name="category" value="<?= htmlspecialchars($editRow["category"]) ?>" required placeholder="شعر / بشرة / أظافر...">
          </div>
          <div>
            <label>المدة (دقيقة)</label>
            <input type="number" name="duration_min" value="<?= (int)$editRow["duration_min"] ?>" required min="1">
          </div>
          <div>
            <label>السعر (₪)</label>
            <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($editRow["price"]) ?>" required min="0.01">
          </div>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <button class="btn btn2" type="submit">حفظ التعديل</button>
          <a class="btn" href="services-admin.php">إلغاء</a>
        </div>
      </form>

    <?php else: ?>
      <h2>إضافة خدمة جديدة</h2>
      <form method="POST" class="row-1">
        <input type="hidden" name="action" value="add">

        <div class="row">
          <div>
            <label>اسم الخدمة</label>
            <input name="name" required placeholder="مثلاً: هيدرافيشيال">
          </div>
          <div>
            <label>التصنيف</label>
            <input name="category" required placeholder="شعر / بشرة / أظافر...">
          </div>
          <div>
            <label>المدة (دقيقة)</label>
            <input type="number" name="duration_min" required min="1" placeholder="60">
          </div>
          <div>
            <label>السعر (₪)</label>
            <input type="number" step="0.01" name="price" required min="0.01" placeholder="250">
          </div>
        </div>

        <button class="btn btn2" type="submit">إضافة</button>
      </form>
    <?php endif; ?>
  </div>

  <div class="box">
    <h2>كل الخدمات</h2>
    <div style="overflow:auto">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>الاسم</th>
            <th>التصنيف</th>
            <th>المدة</th>
            <th>السعر</th>
            <th>الحالة</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($rows->num_rows === 0): ?>
            <tr><td colspan="7">ما في خدمات لسه.</td></tr>
          <?php else: ?>
            <?php while($r = $rows->fetch_assoc()): ?>
              <tr>
                <td><?= (int)$r["id"] ?></td>
                <td><?= htmlspecialchars($r["name"]) ?></td>
                <td><?= htmlspecialchars($r["category"]) ?></td>
                <td><?= (int)$r["duration_min"] ?> دقيقة</td>
                <td><?= htmlspecialchars($r["price"]) ?> ₪</td>
                <td>
                  <?php if ((int)$r["active"] === 1): ?>
                    <span class="pill on">مفعّلة</span>
                  <?php else: ?>
                    <span class="pill off">موقوفة</span>
                  <?php endif; ?>
                </td>
                <td style="white-space:nowrap">
                  <a class="btn" href="services-admin.php?edit=<?= (int)$r["id"] ?>">تعديل</a>
                  <a class="btn btn2" href="services-admin.php?toggle=<?= (int)$r["id"] ?>">
                    <?= ((int)$r["active"]===1) ? "تعطيل" : "تفعيل" ?>
                  </a>
                  <a class="btn danger" href="services-admin.php?delete=<?= (int)$r["id"] ?>"
                     onclick="return confirm('متأكدة بدك تحذفي الخدمة؟ إذا عليها حجوزات الأفضل تعطيل.');">
                    حذف
                  </a>
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
