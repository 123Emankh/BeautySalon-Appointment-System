<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صالون الجمال - أرقى خدمات التجميل</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Cairo:wght@300;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Cairo',sans-serif; line-height:1.7; color:#333; direction:rtl; }
        h1,h2,h3 { font-family:'Playfair Display',serif; }
        .navbar { position:fixed; top:0; left:0; right:0; background:rgba(255,255,255,0.97); padding:1rem 5%; display:flex; justify-content:space-between; align-items:center; z-index:1000; box-shadow:0 4px 20px rgba(0,0,0,0.1); }
        .navbar h1 { color:#d4a86a; font-size:2.2rem; }
        .navbar nav a { margin:0 1rem; text-decoration:none; color:#555; font-weight:600; transition:0.3s; }
        .navbar nav a:hover { color:#d4a86a; }
        .hero { height:100vh; background:linear-gradient(rgba(0,0,0,0.6),rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1600948836101-f9ffda59d250?w=1920') center/cover; display:flex; align-items:center; justify-content:center; text-align:center; color:white; }
        .hero h2 { font-size:4.5rem; margin-bottom:1rem; }
        .hero p { font-size:1.4rem; margin-bottom:2rem; }
        .btn-primary { background:#d4a86a; color:white; padding:1rem 2.5rem; border:none; border-radius:50px; text-decoration:none; display:inline-block; font-size:1.2rem; box-shadow:0 5px 20px rgba(212,168,106,0.4); transition:0.3s; cursor:pointer;}
        .btn-primary:hover { background:#c89b56; transform:translateY(-5px); }
        .section { padding:100px 5%; text-align:center; }
        .section-title { font-size:3rem; color:#d4a86a; margin-bottom:4rem; }
        .bg-light { background:#fdf8f5; }
        .services-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:2rem; max-width:1200px; margin:0 auto; }
        .service-card { background:white; padding:2.5rem; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,0.1); transition:0.4s; }
        .service-card:hover { transform:translateY(-15px); }
        .service-card .icon { font-size:3.5rem; margin-bottom:1rem; }
        .gallery-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:1rem; max-width:1400px; margin:0 auto; }
        .gallery-grid img { width:100%; height:250px; object-fit:cover; border-radius:15px; cursor:pointer; transition:0.3s; }
        .gallery-grid img:hover { transform:scale(1.05); }
        .fixed-booking-btn { position:fixed; bottom:20px; left:50%; transform:translateX(-50%); background:#d4a86a; color:white; padding:1rem 2rem; border-radius:50px; text-decoration:none; box-shadow:0 5px 20px rgba(0,0,0,0.3); z-index:999; display:none; }
        @media (max-width:768px) { .fixed-booking-btn { display:block; } .hero h2 { font-size:3rem; } }
        .whatsapp-float { position:fixed; bottom:30px; left:30px; background:#25d366; color:white; width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:30px; box-shadow:0 5px 20px rgba(0,0,0,0.3); z-index:1000; }
        #lightbox { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); align-items:center; justify-content:center; z-index:2000; }
        #lightbox img { max-width:90%; max-height:90%; border-radius:15px; }
        .testimonial { background:white; padding:2rem; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.1); margin:1rem auto; max-width:700px; }
        footer { background:#1a1a1a; color:#ddd; text-align:center; padding:2rem; }

        .login-section { max-width: 400px; margin: auto; text-align: center; font-family: "Cairo", sans-serif; }
        .tabs { display: flex; justify-content: space-around; margin-bottom: 20px; font-size: 18px; }
        .tab { padding: 8px 0; cursor: pointer; color: #777; width: 50%; }
        .tab.active { color: #c79a5a; border-bottom: 2px solid #c79a5a; }
        .label { display: block; margin: 10px 0 5px; text-align: right; font-size: 16px; }
        .input { width: 100%; padding: 14px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 15px; background: #fdfbf6; font-size: 15px; outline: none; }
        .btn-primary.login-btn { width: 100%; padding: 14px; background: #c79a5a; color: white; border: none; border-radius: 30px; font-size: 18px; cursor: pointer; }
        .guest-link { margin-top: 15px; }
        .guest-link a { color: #c79a5a; text-decoration: none; font-size: 15px; }
        .form { display: none; }
        .form.active { display: block; }

        .mini-msg{margin:10px 0;padding:10px;border-radius:10px;font-weight:700}
        .ok{background:#d4edda;color:#155724}
        .err{background:#f8d7da;color:#721c24}
    </style>
</head>
<body>

<header class="navbar">
    <h1 class="logo">
        <span class="crown">✧</span>
        Emalen
        <span class="gold-text">Salon</span>
        <span class="flourish">❧</span>
    </h1>
    <nav>
        <a href="#home">الرئيسية</a>
        <a href="#about">من نحن</a>
        <a href="#services">الخدمات</a>
        <a href="#gallery">معرض الأعمال</a>
        <a href="#testimonials">آراء العملاء</a>
  <a href="login.php?next=booking.php" class="btn">احجزي موعدك</a>
        <a href="shop.php">المتجر</a>
        <a href="cart.php">السلة</a>
<a href="admin-login.php">المدير</a>
<a href="booking_guest.php" class="btn">
احجزي كزائرة
</a>

        <?php if (isset($_SESSION["user_id"])): ?>
            <a href="booking.php">حجوزاتي</a>
            <a href="logout.php">خروج</a>
        <?php endif; ?>
    </nav>
</header>

<section id="home" class="hero">
    <div class="hero-content">
        <h2>جمالكِ يبدأ من هنا</h2>
        <p>استمتعي بأرقى خدمات التجميل في أجواء فخمة ومريحة</p>
     
        <a href="login.php?next=booking.php" class="btn-primary">احجزي الآن</a>

    </div>
</section>

<section id="about" class="section">
    <h2 class="section-title">من نحن</h2>
    <p class="about-text"> ✨ صالون Emalen هو وجهتكِ الأولى للعناية بالجمال في فلسطين، حيث نضع أنوثتكِ وتألقكِ في صميم كل ما نقدّمه. منذ تأسيسنا عام 2018، حرصنا على الجمع بين الخبرة العالمية في عالم التجميل واللمسة العربية الأصيلة، لنوفّر لكِ تجربة متكاملة تشعركِ بالثقة والتميّز في كل زيارة.

        في إيمالين، نؤمن أن الجمال ليس مجرد مظهر، بل إحساس ينبع من العناية، الراحة، والاهتمام بأدق التفاصيل. لذلك نعمل باستخدام أحدث التقنيات وأجود المنتجات العالمية، على أيدي فريق محترف من الخبيرات المتخصصات في العناية بالشعر، البشرة، المكياج، والعناية المتكاملة بالجسم. نحرص دائمًا على تقديم خدمات مخصّصة تناسب ذوقكِ واحتياجاتكِ، سواء كنتِ تستعدين لمناسبة خاصة أو تبحثين عن لحظة استرخاء وتجديد.

        نعدكِ في صالون إيمالين بتجربة راقية، أجواء أنيقة، وخدمة تهتم بكِ من اللحظة الأولى وحتى خروجكِ بابتسامة وثقة أكبر بنفسكِ، لأنكِ تستحقين الأفضل دائمًا ✨</p>
</section>

<section id="services" class="section bg-light">
    <h2 class="section-title">خدماتنا الفاخرة</h2>
    <div class="services-grid">
        <div class="service-card"><div class="icon">✂️</div><h3>قص وتسريح</h3><p>أحدث القصات مع خبراء عالميين</p></div>
        <div class="service-card"><div class="icon">💇‍♀️</div><h3>صبغات وعلاجات</h3><p>كيراتين، بوتوكس، بروتين</p></div>
        <div class="service-card"><div class="icon">💄</div><h3>مكياج احترافي</h3><p>عرائس ومناسبات خاصة</p></div>
        <div class="service-card"><div class="icon">✨</div><h3>العناية بالبشرة</h3><p>هيدرافيشيال وبلازما</p></div>
        <div class="service-card"><div class="icon">💅</div><h3>مانيكير وبديكير</h3><p>جل وأكريليك بألوان عصرية</p></div>
        <div class="service-card"><div class="icon">🌿</div><h3>مساج وعلاجات استرخاء</h3><p>جلسات تدليك فاخرة</p></div>
    </div>
</section>

<section id="gallery" class="section">
    <h2 class="section-title">أعمالنا</h2>
    <div class="gallery-grid">
        <img src="1.jpg" style="height:400px; object-fit:cover;" onclick="openLightbox(this)">
        <img src="2.jpg" style="height:400px; object-fit:cover;" onclick="openLightbox(this)">
        <img src="3.jpg" style="height:400px; object-fit:cover;" onclick="openLightbox(this)">
        <img src="4.jpg" style="height:400px; object-fit:cover;" onclick="openLightbox(this)">
        <img src="5jpg.jpg" style="height:400px; object-fit:cover;" onclick="openLightbox(this)">
        <img src="7.jpg" style="height:400px; object-fit:cover;" onclick="openLightbox(this)">
        <img src="8.jpg" style="height:400px; object-fit:cover;" onclick="openLightbox(this)">
        <img src="9.jpg" style="height:400px; object-fit:cover;" onclick="openLightbox(this)">
    </div>
</section>

<section id="testimonials" class="section bg-light">
    <h2 class="section-title">ماذا قالت زبائننا</h2>
    <div class="testimonials-slider">
        <div class="testimonial">تجربة راقية جدًا، المكياج كان تحفة والمعاملة أروع ❤️<br><strong>- لمى السعدي</strong></div>
        <div class="testimonial">أفضل صالون جربتُه في فلسطين، الشعر طلع زي الحرير بعد الكيراتين<br><strong>- ريم الدوسري</strong></div>
        <div class="testimonial">من ٣ سنوات وأنا زبونة دائمة، ما أغير المكان أبدًا<br><strong>- سارة العبدلي</strong></div>
    </div>
</section>

<section id="booking" class="section login-section">
    <div class="tabs">
        <span class="tab active" data-tab="login">تسجيل الدخول</span>
        <span class="tab" data-tab="register">إنشاء حساب</span>
    </div>

    <!-- Login -->
    <form class="form active" id="login" method="POST" action="login.php">
        <label class="label">رقم الجوال أو الإيميل</label>
        <input type="text" class="input" name="identifier" placeholder="email@example.com أو 05xxxxxxxx" required>

        <label class="label">كلمة المرور</label>
        <input type="password" class="input" name="password" placeholder="••••••" required>

        <button type="submit" class="btn-primary login-btn">تسجيل الدخول</button>

        <div class="guest-link">
            <a href="booking.php"> أدخل كزائر </a>
        </div>
    </form>

    <!-- Register -->
    <form class="form" id="register" method="POST" action="register.php">
        <label class="label">الاسم الكامل</label>
        <input type="text" class="input" name="full_name" placeholder="اسمك الكريم" required>

        <label class="label">رقم الجوال (واتساب)</label>
        <input type="tel" class="input" name="phone" placeholder="05xxxxxxxx" required>

        <label class="label">الإيميل (اختياري)</label>
        <input type="email" class="input" name="email" placeholder="email@example.com">

        <label class="label">كلمة المرور</label>
        <input type="password" class="input" name="password" minlength="6" placeholder="••••••" required>

        <button type="submit" class="btn-primary login-btn">إنشاء الحساب</button>
    </form>
</section>

<a href="#booking" class="fixed-booking-btn">
    <i class="fas fa-calendar-alt"></i> احجزي الآن
</a>

<a href="https://wa.me/972568328740" class="whatsapp-float" target="_blank">
    <i class="fab fa-whatsapp"></i>
</a>

<footer>
    <p>© 2025 صالون الجمال - كل الحقوق محفوظة | نابلس، فلسطين</p>
</footer>

<div id="lightbox" onclick="this.style.display='none'">
    <img id="lightbox-img">
</div>

<script>
    function openLightbox(img){ document.getElementById("lightbox").style.display="flex"; document.getElementById("lightbox-img").src=img.src; }

    const tabs = document.querySelectorAll(".tab");
    const forms = document.querySelectorAll(".form");

    tabs.forEach(tab => {
        tab.addEventListener("click", () => {
            tabs.forEach(t => t.classList.remove("active"));
            tab.classList.add("active");

            forms.forEach(f => f.classList.remove("active"));
            document.getElementById(tab.dataset.tab).classList.add("active");
        });
    });
</script>

</body>
</html>
