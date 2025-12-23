<?php
// booking_guest.php (زائرة)
global $conn;
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

// حفظ حجز جديد (زائرة)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name       = trim($_POST["guest_name"] ?? "");
  $phone      = trim($_POST["guest_phone"] ?? "");
  $service_id = intval($_POST["service_id"] ?? 0);
  $date       = $_POST["booking_date"] ?? "";
  $time       = $_POST["booking_time"] ?? "";
  $notes      = trim($_POST["notes"] ?? "");

  if ($name === "" || $phone === "" || $service_id <= 0 || $date === "" || $time === "") {
    $error = "لازم تعبّي: الاسم + رقم الجوال + خدمة + تاريخ + وقت.";
  } else {
    // منع الحجز في الماضي
    $slot_ts = strtotime($date . " " . $time);
    if ($slot_ts === false || $slot_ts < time()) {
      $error = "ما بزبط تحجزي موعد في وقت ماضي.";
    } else {
      try {
        $stmt = $conn->prepare("
          INSERT INTO bookings
            (user_id, guest_name, guest_phone, service_id, booking_date, booking_time, notes, status)
          VALUES
            (NULL, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param("ssisss", $name, $phone, $service_id, $date, $time, $notes);
        $stmt->execute();

        header("Location: booking_guest.php?ok=1");
        exit();

      } catch (mysqli_sql_exception $e) {
        $code = (int)$e->getCode();

        // 1054 = Unknown column (يعني ما عملتي ALTER TABLE)
        if ($code === 1054) {
          $error = "قاعدة البيانات عندك ناقصها أعمدة الزائر. لازم تعملي ALTER TABLE (موجود تحت).";
        }
        // 1062 = Duplicate (إذا عاملة UNIQUE على (service_id, booking_date, booking_time))
        elseif ($code === 1062) {
          $error = "هذا الوقت محجوز بالفعل لهذه الخدمة. اختاري وقت ثاني.";
        } else {
          $error = "صار خطأ غير متوقع.";
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>حجز موعد (زائرة) - إمالين صالون</title>
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

    .row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .row-1{display:grid;grid-template-columns:1fr;gap:16px}

    label{font-weight:900;color:#111}

    .input-group input,
    .input-group select,
    .input-group textarea{
      width:100%;
      padding:12px;
      border-radius:12px;
      border:1px solid rgba(0,0,0,.15);
      font-family:'Cairo',sans-serif;
      outline:none;
      background:#fff;
    }
    .input-group input:focus,
    .input-group select:focus,
    .input-group textarea:focus{
      border-color:rgba(212,168,106,.9);
      box-shadow:0 0 0 4px rgba(212,168,106,.18);
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
    .btn:hover{background:#d4a86a;color:#111}

    .msg{
      padding:12px;
      border-radius:12px;
      font-weight:900;
      margin:10px 0;
      border:1px solid rgba(0,0,0,.06);
    }
    .ok{background:rgba(20,120,70,.12);color:#0d3b24;border-color:rgba(20,120,70,.22)}
    .err{background:rgba(176,0,32,.14);color:#5a0011;border-color:rgba(176,0,32,.22)}

    code.sql{
      display:block;
      background:#111;
      color:#f3f3f3;
      padding:12px;
      border-radius:12px;
      overflow:auto;
      direction:ltr;
      text-align:left;
      border:1px solid rgba(212,168,106,.35);
    }

    @media (max-width: 900px){
      .row{grid-template-columns:1fr}
    }
  </style>
</head>

<body>

<div class="page-wrap">

  <div class="card-box top-actions">
    <div class="hello">أهلًا فيكِ زائرة 👋</div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a class="btn" href="emalen.php">الصفحة الرئيسية</a>
      <a class="btn" href="login.php">تسجيل دخول</a>
    </div>
  </div>

  <div class="card-box">
    <h2 style="margin:0 0 16px;color:#111;font-weight:900">احجزي موعدك (كزائرة)</h2>

    <?php if (isset($_GET["ok"])): ?>
      <div class="msg ok">تم إرسال الحجز ✅ (بانتظار التأكيد)</div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
      <div class="msg err"><?= htmlspecialchars($error) ?></div>

      <?php if (strpos($error, "ALTER TABLE") !== false || strpos($error, "أعمدة الزائر") !== false): ?>
        <div style="margin-top:10px">
          <div style="font-weight:900;margin-bottom:6px;color:#111">نفّذي هذا الاستعلام في phpMyAdmin → SQL:</div>
          <code class="sql">ALTER TABLE bookings
ADD guest_name VARCHAR(100) NULL,
ADD guest_phone VARCHAR(20) NULL;</code>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <form method="POST" class="row-1">
      <div class="row">

        <div class="input-group">
          <label>اسمك</label>
          <input type="text" name="guest_name" required placeholder="مثلاً: فاطمة أحمد"
                 value="<?= htmlspecialchars($_POST["guest_name"] ?? "") ?>">
        </div>

        <div class="input-group">
          <label>رقم الجوال</label>
          <input type="tel" name="guest_phone" required placeholder="مثلاً: 059xxxxxxx"
                 value="<?= htmlspecialchars($_POST["guest_phone"] ?? "") ?>">
        </div>

        <div class="input-group">
          <label>الخدمة</label>
          <select name="service_id" required>
            <option value="">اختاري خدمة</option>
            <?php while($s = $services->fetch_assoc()): ?>
              <option value="<?= (int)$s["id"] ?>" <?= ((int)($_POST["service_id"] ?? 0) === (int)$s["id"]) ? "selected" : "" ?>>
                <?= htmlspecialchars($s["name"]) ?> — <?= (int)$s["duration_min"] ?> دقيقة — <?= htmlspecialchars($s["price"]) ?> ₪
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="input-group">
          <label>التاريخ</label>
          <input type="date" name="booking_date" required value="<?= htmlspecialchars($_POST["booking_date"] ?? "") ?>">
        </div>

        <div class="input-group">
          <label>الوقت</label>
          <input type="time" name="booking_time" required value="<?= htmlspecialchars($_POST["booking_time"] ?? "") ?>">
        </div>

        <div class="input-group">
          <label>ملاحظة (اختياري)</label>
          <input type="text" name="notes" placeholder="مثلاً: بحب ميك أب ناعم..."
                 value="<?= htmlspecialchars($_POST["notes"] ?? "") ?>">
        </div>

      </div>

      <button class="btn" type="submit">تأكيد الحجز</button>
    </form>
  </div>

  <div class="card-box">
    <h3 style="margin:0 0 8px;color:#111;font-weight:900">معلومة سريعة</h3>
    <div style="color:#333;line-height:1.8;font-weight:700">
      بعد ما تبعتي الحجز، الإدارة بتأكد الموعد من لوحة المدير.
      إذا بدك تتابعي “حجوزاتي” بشكل مرتب، سجّلي حساب وبصير كل إشي مربوط باسمك.
    </div>
  </div>

</div>

</body>
</html>
