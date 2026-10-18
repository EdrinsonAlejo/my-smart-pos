<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";

// Productos registrados
$resultadoProductos = $conexion->query("SELECT COUNT(*) AS total FROM productos");
$totalProductos = $resultadoProductos->fetch_assoc()["total"];

// Ventas realizadas hoy
$resultadoVentas = $conexion->query(
    "SELECT COUNT(*) AS total, COALESCE(SUM(total), 0) AS ingresos
     FROM ventas
     WHERE DATE(fecha) = CURDATE()"
);

$datosVentas = $resultadoVentas->fetch_assoc();
$ventasHoy = $datosVentas["total"];
$ingresosHoy = $datosVentas["ingresos"];

// Productos con stock bajo
$resultadoStock = $conexion->query(
    "SELECT COUNT(*) AS total
     FROM productos
     WHERE stock <= stock_minimo"
);

$stockBajo = $resultadoStock->fetch_assoc()["total"];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel principal | My Smart POS</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="app">

    <aside class="sidebar">

        <div class="sidebar-logo">
            <h2>My Smart POS</h2>
            <span>Punto de venta</span>
        </div>

        <nav>
            <a href="dashboard.php" class="active">Panel principal</a>
            <a href="productos.php">Productos</a>
            <a href="ventas.php">Nueva venta</a>
            <a href="historial.php">Historial de ventas</a>
        </nav>

        <a href="logout.php" class="logout">Cerrar sesión</a>

    </aside>

    <main class="main-content">

        <header class="topbar">

            <div>
                <h1>Panel principal</h1>
                <p>Resumen general de My Smart POS</p>
            </div>

            <div class="user-info">
                <span>Bienvenido,</span>
                <strong>
                    <?php echo htmlspecialchars($_SESSION["nombre"]); ?>
                </strong>
            </div>

        </header>

        <section class="stats">

            <div class="stat-card">
                <span>Productos</span>
                <strong><?php echo $totalProductos; ?></strong>
                <small>Productos registrados</small>
            </div>

            <div class="stat-card">
                <span>Ventas de hoy</span>
                <strong><?php echo $ventasHoy; ?></strong>
                <small>Operaciones realizadas</small>
            </div>

            <div class="stat-card">
                <span>Ingresos de hoy</span>
                <strong>
                    RD$ <?php echo number_format($ingresosHoy, 2); ?>
                </strong>
                <small>Total vendido</small>
            </div>

            <div class="stat-card">
                <span>Stock bajo</span>
                <strong><?php echo $stockBajo; ?></strong>
                <small>Productos por reponer</small>
            </div>

        </section>

        <section class="welcome-panel">

            <h2>Gestión del negocio</h2>

            <p>
                Desde este panel puedes acceder a las principales
                funciones del sistema.
            </p>

            <div class="quick-actions">

                <a href="productos.php" class="action-card">
                    <strong>Productos</strong>
                    <span>Registrar y consultar productos</span>
                </a>

                <a href="ventas.php" class="action-card">
                    <strong>Nueva venta</strong>
                    <span>Registrar una operación de venta</span>
                </a>

                <a href="historial.php" class="action-card">
                    <strong>Historial</strong>
                    <span>Consultar las ventas realizadas</span>
                </a>

            </div>

        </section>

    </main>

</div>

</body>
</html>