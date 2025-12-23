<?php
global $conn;
require "auth.php";
require_admin();
require "db.php";
require_once "helpers.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// إنشاء جدول المنتجات
$conn->query("
  CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(80) NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image_url VARCHAR(255) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$msg=""; 
$err="";

// إضافة / تعديل
if ($_SERVER["REQUEST_METHOD"]==="POST") {
  $id=(int)($_POST["id"] ?? 0);
  $name=trim($_POST["name"] ?? "");
  $category=trim($_POST["category"] ?? "");
  $desc=trim($_POST["description"] ?? "");
  $price=(float)($_POST["price"] ?? 0);
  $stock=(int)($_POST["stock"] ?? 0);
  $image=trim($_POST["image_url"] ?? "");
  $active=(int)($_POST["active"] ?? 1);

  if ($name==="" || $price<=0) {
    $err="الاسم والسعر مطلوبين.";
  } else {
    if ($id>0) {
      $st=$conn->prepare("UPDATE products SET name=?, category=?, description=?, price=?, stock=?, image_url=?, active=? WHERE id=?");
      $st->bind_param("sssdisii",$name,$category,$desc,$price,$stock,$image,$active,$id);
      $st->execute();
      $msg="تم التعديل ✅";
    } else {
      $st=$conn->prepare("INSERT INTO products (name,category,description,price,stock,image_url,active) VALUES (?,?,?,?,?,?,?)");
      $st->bind_param("sssdisi",$name,$category,$desc,$price,$stock,$image,$active);
      $st->execute();
      $msg="تمت الإضافة ✅";
    }
  }
}

// حذف
if (isset($_GET["delete"])) {
  $id=(int)$_GET["delete"];
  $st=$conn->prepare("DELETE FROM products WHERE id=?");
  $st->bind_param("i",$id);
  $st->execute();
  header("Location: products-admin.php");
  exit();
}

// جلب للتعديل
$edit=null;
if (isset($_GET["edit"])) {
  $id=(int)$_GET["edit"];
  $st=$conn->prepare("SELECT * FROM products WHERE id=?");
  $st->bind_param("i",$id);
  $st->execute();
  $edit=$st->get_result()->fetch_assoc();
}

$products=$conn->query("SELECT * FROM products ORDER BY created_at DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>إدارة المنتجات - Emalen Salon</title>
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
    }

    h2{color:#111;margin-bottom:10px}

    .top{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      flex-wrap:wrap;
      margin-bottom:14px;
    }

    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .row-1{display:grid;grid-template-columns:1fr;gap:12px}

    label{font-weight:900;color:#333}

    input,textarea,select{
      width:100%;
      padding:12px;
      border-radius:12px;
      border:1px solid #d7d7d7;
      font-family:'Cairo',sans-serif;
    }

    input:focus,textarea:focus,select:focus{
      outline:none;
      border-color:#d4a86a;
      box-shadow:0 0 0 3px rgba(212,168,106,.22);
    }

    /* Buttons */
    .btn{
      background:#111;
      color:#d4a86a;
      border:1px solid #d4a86a;
      border-radius:999px;
      padding:10px 18px;
      cursor:pointer;
      font-weight:900;
      text-decoration:none;
      transition:.25s;
      display:inline-block;
    }

    .btn:hover{
      background:#d4a86a;
      color:#111;
    }

    .danger{
      background:#b00020;
      border-color:#b00020;
      color:#fff;
    }

    .danger:hover{filter:brightness(.95)}

    table{width:100%;border-collapse:collapse}
    th,td{
      padding:12px;
      border-bottom:1px solid rgba(0,0,0,.08);
      text-align:right;
      vertical-align:top;
      font-weight:800;
    }
    th{color:#111}

    .msg{
      padding:12px;
      border-radius:12px;
      font-weight:900;
      margin-bottom:10px;
    }
    .ok{background:rgba(212,168,106,.22);border:1px solid rgba(212,168,106,.4)}
    .err{background:rgba(176,0,32,.15);border:1px solid rgba(176,0,32,.3)}

    .muted{color:#666;font-weight:800}

    @media(max-width:900px){
      .row{grid-template-columns:1fr}
    }
  </style>
</head>

<body>

<div class="wrap">

  <div class="card top">
    <h2>🧴 إدارة المنتجات</h2>
    <a class="btn" href="admin-dashboard.php">لوحة الأدمن</a>
  </div>

  <div class="card">
    <?php if($msg): ?><div class="msg ok"><?= h($msg) ?></div><?php endif; ?>
    <?php if($err): ?><div class="msg err"><?= h($err) ?></div><?php endif; ?>

    <form method="POST" class="row-1">
      <input type="hidden" name="id" value="<?= (int)($edit["id"] ?? 0) ?>">

      <div class="row">
        <div>
          <label>اسم المنتج</label>
          <input name="name" required value="<?= h($edit["name"] ?? "") ?>">
        </div>
        <div>
          <label>التصنيف</label>
          <input name="category" value="<?= h($edit["category"] ?? "") ?>">
        </div>
        <div>
          <label>السعر (₪)</label>
          <input type="number" step="0.01" name="price" required value="<?= h($edit["price"] ?? "") ?>">
        </div>
        <div>
          <label>المخزون</label>
          <input type="number" name="stock" min="0" value="<?= h($edit["stock"] ?? 0) ?>">
        </div>
        <div>
          <label>رابط صورة</label>
          <input name="image_url" value="<?= h($edit["image_url"] ?? "") ?>">
        </div>
        <div>
          <label>الحالة</label>
          <select name="active">
            <option value="1" <?= ((int)($edit["active"] ?? 1)===1)?"selected":"" ?>>مفعّل</option>
            <option value="0" <?= ((int)($edit["active"] ?? 1)===0)?"selected":"" ?>>موقوف</option>
          </select>
        </div>
      </div>

      <div>
        <label>الوصف</label>
        <textarea name="description" rows="3"><?= h($edit["description"] ?? "") ?></textarea>
      </div>

      <button class="btn" type="submit"><?= $edit ? "تعديل" : "إضافة" ?></button>
    </form>
  </div>

  <div class="card">
    <div class="muted" style="margin-bottom:10px">هذه المنتجات تظهر في صفحة المتجر.</div>

    <div style="overflow:auto">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>المنتج</th>
            <th>التصنيف</th>
            <th>السعر</th>
            <th>المخزون</th>
            <th>مفعّل</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php if($products->num_rows===0): ?>
            <tr><td colspan="7">ما في منتجات لسه.</td></tr>
          <?php else: ?>
            <?php while($p=$products->fetch_assoc()): ?>
              <tr>
                <td><?= (int)$p["id"] ?></td>
                <td>
                  <b><?= h($p["name"]) ?></b>
                  <?php if($p["description"]): ?>
                    <div class="muted"><?= h(mb_strimwidth($p["description"],0,80,"...","UTF-8")) ?></div>
                  <?php endif; ?>
                </td>
                <td><?= h($p["category"]) ?></td>
                <td><?= h($p["price"]) ?> ₪</td>
                <td><?= (int)$p["stock"] ?></td>
                <td><?= ((int)$p["active"]===1)?"نعم":"لا" ?></td>
                <td style="white-space:nowrap">
                  <a class="btn" href="products-admin.php?edit=<?= (int)$p["id"] ?>">تعديل</a>
                  <a class="btn danger" href="products-admin.php?delete=<?= (int)$p["id"] ?>" onclick="return confirm('متأكدة بدك تحذفيه؟')">حذف</a>
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
