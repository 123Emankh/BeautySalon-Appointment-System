<?php
global $conn;
require "auth.php";
require_login();
require "db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// خدمات
$services = $conn->query("
  SELECT id, name, category, duration_min, price
  FROM services
  WHERE active=1
  ORDER BY category, name
");

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $service_id = intval($_POST["service_id"] ?? 0);
  $date       = $_POST["booking_date"] ?? "";
  $time       = $_POST["booking_time"] ?? "";
  $notes      = trim($_POST["notes"] ?? "");

  if ($service_id <= 0 || $date === "" || $time === "") {
    $error = "لازم تختاري خدمة + تاريخ + وقت.";
  } else {
    $slot_ts = strtotime($date . " " . $time);
    if ($slot_ts === false || $slot_ts < time()) {
      $error = "ما بزبط تحجزي موعد في وقت ماضي.";
    } else {
      try {
        $stmt = $conn->prepare("
          INSERT INTO bookings (user_id, service_id, booking_date, booking_time, notes)
          VALUES (?, ?, ?, ?, ?)
        ");
        $uid = (int)$_SESSION["user_id"];
        $stmt->bind_param("iisss", $uid, $service_id, $date, $time, $notes);
        $stmt->execute();
        header("Location: booking.php?ok=1");
        exit();
      } catch (mysqli_sql_exception $e) {
        $error = ((int)$e->getCode() === 1062)
          ? "هذا الوقت محجوز بالفعل. اختاري وقت ثاني."
          : "صار خطأ غير متوقع.";
      }
    }
  }
}

// حجوزاتي
$stmt = $conn->prepare("
  SELECT b.id,
         s.name AS service_name,
         b.booking_date,
         b.booking_time,
         b.status,
         b.notes,
         b.created_at
  FROM bookings b
  JOIN services s ON s.id = b.service_id
  WHERE b.user_id = ?
  ORDER BY b.created_at DESC
");
$uid = (int)$_SESSION["user_id"];
$stmt->bind_param("i", $uid);
$stmt->execute();
$myBookings = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>حجز موعد - Emalen Salon</title>
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

    .page-wrap{max-width:1000px;margin:40px auto;padding:0 16px}

    .card-box{
      background:rgba(255,255,255,.95);
      border-radius:18px;
      padding:22px;
      box-shadow:0 12px 35px rgba(0,0,0,.15);
      margin-bottom:22px;
    }

    .top-actions{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
    }

    .hello{
      font-weight:900;
      color:#111;
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
    }
    .btn:hover{
      background:#d4a86a;
      color:#111;
    }

    h2{color:#111;margin-bottom:16px}

    .row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .row-1{display:grid;grid-template-columns:1fr;gap:16px}

    label{font-weight:800;color:#333}

    .input-group select,
    .input-group input,
    .input-group textarea{
      width:100%;
      padding:12px;
      border-radius:12px;
      border:1px solid #d7d7d7;
      font-family:'Cairo',sans-serif;
    }

    .input-group select:focus,
    .input-group input:focus{
      outline:none;
      border-color:#d4a86a;
      box-shadow:0 0 0 3px rgba(212,168,106,.22);
    }

    table{width:100%;border-collapse:collapse}
    th,td{
      padding:12px;
      border-bottom:1px solid rgba(0,0,0,.08);
      text-align:right;
      font-weight:800;
    }
    th{color:#111}

    .pill{
      display:inline-block;
      padding:6px 12px;
      border-radius:999px;
      font-weight:900;
      font-size:.85rem;
    }
    .pending{background:rgba(0,0,0,.08)}
    .confirmed{background:rgba(212,168,106,.25)}
    .cancelled{background:rgba(176,0,32,.15);color:#5a0011}

    .msg{
      padding:12px;
      border-radius:12px;
      font-weight:900;
      margin-bottom:12px;
    }
    .ok{background:rgba(212,168,106,.25)}
    .err{background:rgba(176,0,32,.15);color:#5a0011}

    @media(max-width:900px){
      .row{grid-template-columns:1fr}
    }
  </style>
</head>
<body>

<div class="page-wrap">

  <div class="card-box top-actions">
    <div class="hello">أهلًا <?= htmlspecialchars($_SESSION["name"] ?? "") ?> 👋</div>
    <div>
      <a class="btn" href="emalen.php">الرئيسية</a>
      <a class="btn" href="logout.php">تسجيل الخروج</a>
    </div>
  </div>

  <div class="card-box">
    <h2>احجزي موعدك</h2>

    <?php if (isset($_GET["ok"])): ?>
      <div class="msg ok">تم إرسال الحجز ✅ (بانتظار التأكيد)</div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="msg err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="row-1">
      <div class="row">
        <div class="input-group">
          <label>الخدمة</label>
          <select name="service_id" required>
            <option value="">اختاري خدمة</option>
            <?php while($s=$services->fetch_assoc()): ?>
              <option value="<?= (int)$s["id"] ?>">
                <?= htmlspecialchars($s["name"]) ?> — <?= (int)$s["duration_min"] ?> دقيقة — <?= htmlspecialchars($s["price"]) ?> ₪
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="input-group">
          <label>التاريخ</label>
          <input type="date" name="booking_date" required>
        </div>

        <div class="input-group">
          <label>الوقت</label>
          <input type="time" name="booking_time" required>
        </div>

        <div class="input-group">
          <label>ملاحظة (اختياري)</label>
          <input type="text" name="notes">
        </div>
      </div>

      <button class="btn" type="submit">تأكيد الحجز</button>
    </form>
  </div>

  <div class="card-box">
    <h2>حجوزاتي</h2>

    <div style="overflow:auto">
      <table>
        <thead>
          <tr>
            <th>الخدمة</th>
            <th>التاريخ</th>
            <th>الوقت</th>
            <th>الحالة</th>
            <th>تاريخ الطلب</th>
            <th>إجراء</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($myBookings->num_rows===0): ?>
            <tr><td colspan="6">ما عندك حجوزات لسه.</td></tr>
          <?php else: ?>
            <?php while($b=$myBookings->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($b["service_name"]) ?></td>
                <td><?= htmlspecialchars($b["booking_date"]) ?></td>
                <td><?= htmlspecialchars(substr($b["booking_time"],0,5)) ?></td>
                <td>
                  <span class="pill <?= htmlspecialchars($b["status"]) ?>">
                    <?= $b["status"]==="pending"?"بانتظار":($b["status"]==="confirmed"?"مؤكد":"ملغي") ?>
                  </span>
                </td>
                <td><?= htmlspecialchars(date("Y-m-d", strtotime($b["created_at"]))) ?></td>
                <td>
                  <?php if ($b["status"]!=="cancelled"): ?>
                    <form method="POST" action="cancel_booking.php" onsubmit="return confirm('متأكدة بدك تلغي الحجز؟')" style="margin:0">
                      <input type="hidden" name="booking_id" value="<?= (int)$b["id"] ?>">
                      <button class="btn" style="background:#b00020;color:#fff;border-color:#b00020">إلغاء</button>
                    </form>
                  <?php else: ?>
                    ملغي
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (isset($_GET["cancelled"])): ?>
    <div class="msg err">تم إلغاء الحجز بنجاح.</div>
  <?php endif; ?>

</div>
</body>
</html>
