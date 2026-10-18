<?php
$conexion = new mysqli(
    "localhost",
    "root",
    "",
    "my_smart_pos"
);

if ($conexion->connect_error) {
    die("Error de conexión con la base de datos.");
}