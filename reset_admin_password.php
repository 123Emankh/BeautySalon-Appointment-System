<?php
global $conn;
require "db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$email = "admin@emalen.com";
$newPassword = "Admin@123";

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE email=? AND role='admin'");
$stmt->bind_param("ss", $newHash, $email);
$stmt->execute();

echo "DONE ✅ Admin password reset to: Admin@123<br>";
echo "Affected rows: " . $stmt->affected_rows . "<br>";
