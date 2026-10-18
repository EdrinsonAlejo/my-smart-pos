<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";

if (isset($_GET["id"]) && is_numeric($_GET["id"])) {

    $id = (int) $_GET["id"];

    $stmt = $conexion->prepare(
        "DELETE FROM productos WHERE id_producto = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: productos.php");
exit;

?>