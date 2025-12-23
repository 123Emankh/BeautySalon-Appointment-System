<?php
// auth.php
session_start();

function require_login() {
  // user_id لازم يكون موجود و > 0 (عشان الزائر ما ينحسب Login)
  if (!isset($_SESSION["user_id"]) || (int)$_SESSION["user_id"] <= 0) {
    header("Location: login.php");
    exit();
  }
}

function require_admin() {
  if (!isset($_SESSION["user_id"]) || (int)$_SESSION["user_id"] <= 0 || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: admin-login.php");
    exit();
  }
}
