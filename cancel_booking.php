<?php
global $conn;
session_start();
require "db.php";
require "auth.php"; // أو أي ملف تحقق تسجيل الدخول

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["booking_id"])) {
    $booking_id = intval($_POST["booking_id"]);
    $user_id = (int)$_SESSION["user_id"];

    // تحقق إن الحجز ملك المستخدم ومش ملغي أصلاً
    $stmt = $conn->prepare("SELECT id, status FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result && $result["status"] !== "cancelled") {
        // تحديث الحالة إلى cancelled
        $update = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $update->bind_param("i", $booking_id);
        $update->execute();

        // إرسال إشعار للمدير (اختياري: بريد إلكتروني أو جدول إشعارات)
        // مثال بسيط: أضيفي سطر في جدول notifications لو موجود
        // أو استخدمي mail() لو عايزة بريد حقيقي
        // mail("admin@emalen.com", "إلغاء حجز", "تم إلغاء حجز رقم $booking_id من قبل المستخدم $user_id");

        header("Location: booking.php?cancelled=1");
        exit();
    }
}

// لو خطأ، رجعي للصفحة
header("Location: booking.php");
exit();
?>