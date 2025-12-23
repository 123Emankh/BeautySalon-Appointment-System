<?php
// helpers.php
require_once "db.php";

function h($s){ return htmlspecialchars($s ?? "", ENT_QUOTES, "UTF-8"); }

// يرجّع اسم المستخدم حسب الأعمدة الموجودة (full_name أو name)
function user_display_name($row){
  if (isset($row["full_name"]) && $row["full_name"] !== "") return $row["full_name"];
  if (isset($row["name"]) && $row["name"] !== "") return $row["name"];
  return "مستخدم";
}

function users_name_column(mysqli $conn){
  $cols = [];
  $res = $conn->query("SHOW COLUMNS FROM users");
  while($c = $res->fetch_assoc()){ $cols[] = $c["Field"]; }
  if (in_array("full_name",$cols)) return "full_name";
  if (in_array("name",$cols)) return "name";
  return "id";
}
?>