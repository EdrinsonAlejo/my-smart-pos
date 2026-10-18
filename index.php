<?php
session_start();
require_once "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = trim($_POST["usuario"]);
    $contrasena = $_POST["contrasena"];

    $sql = "SELECT * FROM usuarios WHERE usuario = ? AND contrasena = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $usuario, $contrasena);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {

        $datos = $resultado->fetch_assoc();

        $_SESSION["id_usuario"] = $datos["id_usuario"];
        $_SESSION["nombre"] = $datos["nombre"];
        $_SESSION["rol"] = $datos["rol"];

        header("Location: dashboard.php");
        exit;

    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Smart POS</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="login-container">

    <div class="login-box">

        <div class="logo">
            <h1>My Smart POS</h1>
            <p>Sistema de ventas e inventario</p>
        </div>

        <?php if ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label for="usuario">Usuario</label>

            <input
                type="text"
                id="usuario"
                name="usuario"
                placeholder="Ingrese su usuario"
                required
            >

            <label for="contrasena">Contraseña</label>

            <input
                type="password"
                id="contrasena"
                name="contrasena"
                placeholder="Ingrese su contraseña"
                required
            >

            <button type="submit">
                Iniciar sesión
            </button>

        </form>

    </div>

</div>

</body>
</html>