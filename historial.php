<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";


/* ==========================================
   CONSULTAR VENTAS
========================================== */

$sql = "
    SELECT
        v.id_venta,
        v.fecha,
        v.total,
        SUM(dv.cantidad) AS total_unidades,
        COUNT(dv.id_detalle) AS tipos_productos
    FROM ventas v
    INNER JOIN detalle_ventas dv
        ON v.id_venta = dv.id_venta
    GROUP BY
        v.id_venta,
        v.fecha,
        v.total
    ORDER BY v.id_venta DESC
";

$resultado = $conexion->query($sql);

if (!$resultado) {
    die(
        "Error al consultar las ventas: "
        . $conexion->error
    );
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Historial de ventas | My Smart POS
    </title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>

<body>

<div class="app">

    <!-- MENÚ LATERAL -->

    <aside class="sidebar">

        <div class="sidebar-logo">

            <h2>My Smart POS</h2>

            <span>Punto de venta</span>

        </div>

        <nav>

            <a href="dashboard.php">
                Panel principal
            </a>

            <a href="productos.php">
                Productos
            </a>

            <a href="ventas.php">
                Nueva venta
            </a>

            <a
                href="historial.php"
                class="active"
            >
                Historial de ventas
            </a>

        </nav>

        <a
            href="logout.php"
            class="logout"
        >
            Cerrar sesión
        </a>

    </aside>


    <!-- CONTENIDO -->

    <main class="main-content">

        <header class="topbar">

            <div>

                <h1>Historial de ventas</h1>

                <p>
                    Consulta de las operaciones realizadas
                </p>

            </div>


            <div class="user-info">

                <span>Usuario</span>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["nombre"]
                    );
                    ?>
                </strong>

            </div>

        </header>


        <div class="table-card">

            <div class="table-header">

                <div>

                    <h2>Ventas registradas</h2>

                    <p>
                        Historial general de operaciones
                    </p>

                </div>

            </div>


            <div class="table-responsive">

                <table>

                    <thead>

                        <tr>

                            <th>Venta</th>

                            <th>Fecha</th>

                            <th>Productos</th>

                            <th>Unidades</th>

                            <th>Total</th>

                            <th>Acción</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if ($resultado->num_rows > 0): ?>


                        <?php
                        while (
                            $venta =
                            $resultado->fetch_assoc()
                        ):
                        ?>

                            <tr>

                                <!-- VENTA -->

                                <td>

                                    <strong>

                                        #<?php
                                        echo $venta["id_venta"];
                                        ?>

                                    </strong>

                                </td>


                                <!-- FECHA -->

                                <td>

                                    <?php

                                    $fecha =
                                        new DateTime(
                                            $venta["fecha"]
                                        );

                                    echo $fecha->format(
                                        "d/m/Y h:i A"
                                    );

                                    ?>

                                </td>


                                <!-- TIPOS DE PRODUCTOS -->

                                <td>

                                    <?php
                                    echo $venta["tipos_productos"];
                                    ?>

                                    <?php
                                    echo
                                        $venta["tipos_productos"] == 1
                                        ? " producto"
                                        : " productos";
                                    ?>

                                </td>


                                <!-- UNIDADES -->

                                <td>

                                    <?php
                                    echo $venta["total_unidades"];
                                    ?>

                                </td>


                                <!-- TOTAL -->

                                <td>

                                    <strong class="sale-amount">

                                        RD$

                                        <?php
                                        echo number_format(
                                            $venta["total"],
                                            2
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <!-- DETALLE -->

                                <td>

                                    <a
                                        href="detalle_venta.php?id=<?php
                                        echo $venta["id_venta"];
                                        ?>"
                                        class="btn-view"
                                    >
                                        Ver detalle
                                    </a>

                                </td>

                            </tr>

                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="6"
                                class="empty"
                            >

                                No hay ventas registradas.

                            </td>

                        </tr>


                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>

</body>

</html>