<?php
session_start();
include "includes/db.php";

if (isset($_GET['id'])) {
    $cart_id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
}

header("Location: cart.php");
exit;
?>
