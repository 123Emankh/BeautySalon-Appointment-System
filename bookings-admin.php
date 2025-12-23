<?php
global $conn;
require "auth.php";
require_admin();
require "db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// تحديث الحالة
if (isset($_GET["set"], $_GET["id"])) {
  $id = intval($_GET["id"]);
  $set = $_GET["set"];
  if (in_array($set, ["pending","confirmed","cancelled"], true)) {
    $stmt = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
    $stmt->bind_param("si", $set, $id);
    $stmt->execute();
  }
  header("Location: bookings-admin.php");
  exit();
}

$rows = $conn->query("
  SELECT b.id,
         COALESCE(u.full_name, b.guest_name) AS customer_name,
         COALESCE(u.phone, b.guest_phone) AS customer_phone,
         u.email,
         s.name AS service_name,
         b.booking_date, b.booking_time, b.status, b.notes, b.created_at
  FROM bookings b
  LEFT JOIN users u ON u.id = b.user_id
  JOIN services s ON s.id = b.service_id
  ORDER BY b.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>حجوزات الزبائن - Emalen Salon</title>
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

    .wrap{max-width:1200px;margin:30px auto;padding:0 16px}

    .box{
      background:rgba(255,255,255,.94);
      border-radius:18px;
      padding:18px;
      box-shadow:0 12px 35px rgba(0,0,0,.15);
      border:1px solid rgba(255,255,255,.35);
    }

    h2{margin:0 0 12px;color:#111;font-weight:900}
    p{margin:0 0 18px;color:#666;font-weight:800}

    /* generic buttons (Black/Gold) */
    a.btn{
      display:inline-block;
      padding:8px 12px;
      border-radius:999px;
      text-decoration:none;
      font-weight:900;
      margin:2px;
      border:1px solid #d4a86a;
      background:#111;
      color:#d4a86a;
      transition:all .25s ease;
      white-space:nowrap;
    }
    a.btn:hover{
      background:#d4a86a;
      color:#111;
    }

    /* Status action buttons (still luxury but clearer) */
    a.btn.confirm{
      background:#d4a86a;
      color:#111;
      border-color:#d4a86a;
    }
    a.btn.confirm:hover{
      background:#111;
      color:#d4a86a;
    }

    a.btn.cancel{
      background:#b00020;
      color:#fff;
      border-color:#b00020;
    }
    a.btn.cancel:hover{filter:brightness(.95)}

    a.btn.pending{
      background:rgba(0,0,0,.08);
      color:#111;
      border-color:rgba(0,0,0,.18);
    }
    a.btn.pending:hover{
      background:#111;
      color:#d4a86a;
      border-color:#d4a86a;
    }

    table{width:100%;border-collapse:collapse}
    th,td{
      padding:12px;
      border-bottom:1px solid rgba(0,0,0,.08);
      text-align:right;
      vertical-align:top;
      font-weight:800;
    }
    th{color:#111;font-weight:900}

    .muted{color:#666;font-weight:800}

    .pill{
      display:inline-block;
      padding:6px 12px;
      border-radius:999px;
      font-weight:900;
      font-size:.9rem;
      border:1px solid rgba(0,0,0,.12);
      background:rgba(0,0,0,.06);
      color:#111;
    }
    .pill.confirmed{background:rgba(212,168,106,.22);border-color:rgba(212,168,106,.45)}
    .pill.cancelled{background:rgba(176,0,32,.12);border-color:rgba(176,0,32,.25);color:#5a0011}
    .pill.pending{background:rgba(0,0,0,.06);border-color:rgba(0,0,0,.12)}

    .topbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
      margin-bottom:14px;
    }
  </style>
</head>
<body>

<div class="wrap">

  <div class="box">
    <div class="topbar">
      <div>
        <h2>كل الحجوزات</h2>
        <p>الأدمن يقدر يؤكد أو يلغي أو يرجّعها لانتظار.</p>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a class="btn" href="admin-dashboard.php">لوحة الأدمن</a>
        <a class="btn" href="services-admin.php">الخدمات</a>
        <a class="btn confirm" href="logout.php">تسجيل خروج</a>
      </div>
    </div>

    <div style="overflow:auto">
      <table>
        <thead>
          <tr>
            <th>الزبونة</th>
            <th>التواصل</th>
            <th>الخدمة</th>
            <th>التاريخ</th>
            <th>الوقت</th>
            <th>الحالة</th>
            <th>ملاحظة</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($rows->num_rows === 0): ?>
            <tr><td colspan="8">ما في حجوزات.</td></tr>
          <?php else: ?>
            <?php while($r = $rows->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($r["customer_name"]) ?></td>
                <td>
                  <?= htmlspecialchars($r["customer_phone"]) ?><br>
                  <span class="muted"><?= htmlspecialchars($r["email"] ?? "") ?></span>
                </td>
                <td><?= htmlspecialchars($r["service_name"]) ?></td>
                <td><?= htmlspecialchars($r["booking_date"]) ?></td>
                <td><?= htmlspecialchars(substr($r["booking_time"],0,5)) ?></td>
                <td>
                  <?php
                    $st = $r["status"] ?? "pending";
                    $cls = in_array($st, ["pending","confirmed","cancelled"], true) ? $st : "pending";
                  ?>
                  <span class="pill <?= $cls ?>"><?= htmlspecialchars($st) ?></span>
                </td>
                <td><?= htmlspecialchars($r["notes"] ?? "") ?></td>
                <td style="white-space:nowrap">
                  <a class="btn confirm" href="bookings-admin.php?set=confirmed&id=<?= (int)$r["id"] ?>">تأكيد</a>
                  <a class="btn cancel" href="bookings-admin.php?set=cancelled&id=<?= (int)$r["id"] ?>">إلغاء</a>
                  <a class="btn pending" href="bookings-admin.php?set=pending&id=<?= (int)$r["id"] ?>">انتظار</a>
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
