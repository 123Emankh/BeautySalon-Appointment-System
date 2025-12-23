<?php
global $conn;
require "auth.php";
require_admin();
require "db.php";
require_once "helpers.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$nameCol = users_name_column($conn);

// قائمة الزبائن
$q = "
SELECT 
  u.id,
  u.$nameCol AS full_name,
  u.email,
  u.phone,
  u.role,
  COUNT(b.id) AS bookings_count,
  MAX(CONCAT(b.booking_date,' ',b.booking_time)) AS last_booking
FROM users u
LEFT JOIN bookings b ON b.user_id = u.id
WHERE (u.role IS NULL OR u.role <> 'admin')
GROUP BY u.id, u.$nameCol, u.email, u.phone, u.role
ORDER BY u.id DESC
";
$customers = $conn->query($q);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>الزبائن - Emalen Salon</title>
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

    .card{
      background:rgba(255,255,255,.94);
      border-radius:18px;
      padding:18px;
      box-shadow:0 12px 35px rgba(0,0,0,.15);
      margin-bottom:18px;
      border:1px solid rgba(255,255,255,.35);
    }

    .top{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
      margin-bottom:14px;
    }

    h2{
      font-weight:900;
      color:#111;
      font-size:1.35rem;
    }

    .muted{
      color:#666;
      font-weight:800;
      margin-bottom:10px;
    }

    /* Buttons – Black & Gold */
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
      transition:all .25s ease;
      white-space:nowrap;
    }
    .btn:hover{
      background:#d4a86a;
      color:#111;
    }

    table{width:100%;border-collapse:collapse}
    th,td{
      padding:12px;
      border-bottom:1px solid rgba(0,0,0,.08);
      text-align:right;
      vertical-align:middle;
      font-weight:800;
    }
    th{
      color:#111;
      font-weight:900;
    }

    @media(max-width:900px){
      .wrap{padding:0 12px}
    }
  </style>
</head>

<body>

<div class="wrap">

  <div class="top">
    <h2>👩‍🦰 الزبائن</h2>
    <a class="btn" href="admin-dashboard.php">لوحة المدير</a>
  </div>

  <div class="card">
    <div class="muted">كل شخص بيسجّل (غير الأدمن) رح ينعرض هون تلقائيًا.</div>

    <div style="overflow:auto">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>الاسم</th>
            <th>الإيميل</th>
            <th>الجوال</th>
            <th>عدد الحجوزات</th>
            <th>آخر حجز</th>
          </tr>
        </thead>
        <tbody>
          <?php if($customers->num_rows===0): ?>
            <tr><td colspan="6">ما في زبائن لسه.</td></tr>
          <?php else: ?>
            <?php while($c=$customers->fetch_assoc()): ?>
              <tr>
                <td><?= (int)$c["id"] ?></td>
                <td><?= h($c["full_name"]) ?></td>
                <td><?= h($c["email"]) ?></td>
                <td><?= h($c["phone"]) ?></td>
                <td><?= (int)$c["bookings_count"] ?></td>
                <td><?= h($c["last_booking"]) ?></td>
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
