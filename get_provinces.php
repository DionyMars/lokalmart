<?php
require_once __DIR__."/includes/db.php";
$region_id = intval($_GET['region_id']);
$stmt = $conn->prepare("SELECT id,name FROM provinces WHERE region_id=? ORDER BY name ASC");
$stmt->bind_param("i", $region_id);
$stmt->execute();
$result = $stmt->get_result();
$provinces = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($provinces);
