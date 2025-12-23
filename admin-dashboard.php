<?php
require "auth.php";
require_admin();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>لوحة المدير - Emalen Salon</title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
body{
  font-family:'Cairo',sans-serif;
  color:#333;
  background:
    linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),
    url("admin.jpg");
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
}

    .wrap{max-width:1200px;margin:28px auto;padding:0 16px}
    .topbar{background:#fff;border-radius:18px;padding:16px 18px;box-shadow:0 10px 30px rgba(0,0,0,0.08);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
    .title{font-weight:900;color:#6d3a75;font-size:1.35rem}
    .subtitle{color:#666;font-weight:700;margin-top:4px}
    .nav{display:flex;gap:8px;flex-wrap:wrap}
    .btn{
  display:inline-block;
  background:#111;
  color:#d4a86a;
  text-decoration:none;
  padding:10px 16px;
  border-radius:999px;
  font-weight:800;
  font-size:.95rem;
  border:1px solid #d4a86a;
  transition:all .25s ease;
}

.btn:hover{
  background:#d4a86a;
  color:#111;
}

.btn.gold{
  background:#d4a86a;
  color:#111;
  border:1px solid #d4a86a;
}

.btn.gold:hover{
  background:#111;
  color:#d4a86a;
}

    .grid{margin-top:18px;display:grid;grid-template-columns:repeat(12,1fr);gap:14px}
    .card{background:#fff;border-radius:18px;padding:18px;box-shadow:0 10px 30px rgba(0,0,0,0.08);border:1px solid rgba(0,0,0,0.03)}
    .card h3{color:#4b2c4f;font-weight:900;margin-bottom:6px}
    .card p{color:#777;font-weight:700;line-height:1.6}
    .card .actions{margin-top:12px;display:flex;gap:8px;flex-wrap:wrap}
    .col-3{grid-column:span 3}
    .col-4{grid-column:span 4}
    .col-6{grid-column:span 6}
    @media (max-width: 900px){
      .col-3,.col-4,.col-6{grid-column:span 12}
      .title{font-size:1.2rem}
    }
  </style>
</head>
<body>

<div class="wrap">

  <div class="topbar">
    <div>
      <div class="title">لوحة المدير — Emalen Salon</div>
      <div class="subtitle">أهلًا <?= htmlspecialchars($_SESSION["name"] ?? "أدمن"); ?> 👋</div>
    </div>
    <div class="nav">
      <a class="btn" href="services-admin.php">الخدمات</a>
      <a class="btn" href="products-admin.php">المنتجات</a>
      <a class="btn" href="bookings-admin.php">المواعيد</a>
      <a class="btn" href="customers-admin.php">الزبائن</a>
      <a class="btn" href="orders-admin.php">طلبات المتجر</a>
      <a class="btn gold" href="logout.php">تسجيل خروج</a>
    </div>
  </div>

  <div class="grid">

    <div class="card col-4">
      <h3>إدارة الخدمات</h3>
      <p>إضافة / تعديل / تعطيل خدمات الصالون.</p>
      <div class="actions">
        <a class="btn" href="services-admin.php">فتح</a>
      </div>
    </div>

    <div class="card col-4">
      <h3>إدارة المنتجات</h3>
      <p>إضافة منتجات المتجر وتحديث الأسعار والصور.</p>
      <div class="actions">
        <a class="btn" href="products-admin.php">فتح</a>
      </div>
    </div>

    <div class="card col-4">
      <h3>إدارة المواعيد</h3>
      <p>عرض الحجوزات وتأكيدها أو إلغاؤها.</p>
      <div class="actions">
        <a class="btn" href="bookings-admin.php">فتح</a>
      </div>
    </div>

    <div class="card col-6">
      <h3>الزبائن</h3>
      <p>عرض قائمة الزبائن المسجّلين ومتابعة نشاطهم.</p>
      <div class="actions">
        <a class="btn" href="customers-admin.php">فتح</a>
      </div>
    </div>

    <div class="card col-6">
      <h3>طلبات المتجر</h3>
      <p>متابعة الطلبات الجديدة وتحديث حالتها، مع إمكانية فتح المتجر بسرعة.</p>
      <div class="actions">
        <a class="btn" href="orders-admin.php">عرض الطلبات</a>
        <a class="btn gold" href="shop.php">فتح المتجر</a>
      </div>
    </div>

  </div>

</div>

</body>
</html>
