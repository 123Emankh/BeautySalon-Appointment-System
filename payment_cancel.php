<?php
require "auth.php";
require_login();

$order_id = (int)($_GET["order_id"] ?? 0);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>إلغاء الدفع - Emalen</title>
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

    .wrap{max-width:700px;margin:40px auto;padding:0 16px}

    .card{
      background:rgba(255,255,255,.95);
      border-radius:18px;
      padding:22px;
      box-shadow:0 12px 35px rgba(0,0,0,.15);
    }

    .title{
      font-weight:900;
      color:#111;
      font-size:1.4rem;
      margin-bottom:10px;
    }

    .err{
      background:rgba(176,0,32,.15);
      color:#5a0011;
      padding:12px;
      border-radius:12px;
      font-weight:900;
      margin-top:12px;
      line-height:1.7;
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

    .btn.danger{
      background:#b00020;
      border-color:#b00020;
      color:#fff;
    }
    .btn.danger:hover{filter:brightness(.95)}
  </style>
</head>

<body>
  <div class="wrap">
    <div class="card">
      <div class="title">❌ تم إلغاء الدفع</div>

      <div class="err">
        ما تم خصم أي مبلغ. تقدري ترجعي تحاولي الدفع من جديد.
      </div>

      <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap">
        <a class="btn" href="pay.php?order_id=<?= (int)$order_id ?>">ارجعي للدفع</a>
        <a class="btn" href="my-orders.php">طلباتي</a>
      </div>
    </div>
  </div>
</body>
</html>
