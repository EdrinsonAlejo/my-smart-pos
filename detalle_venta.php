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
   OBTENER ID DE LA VENTA
========================================== */

if (
    !isset($_GET["id"])
    || !is_numeric($_GET["id"])
) {

    header("Location: historial.php");
    exit;
}

$idVenta = intval($_GET["id"]);


/* ==========================================
   CONSULTAR INFORMACIÓN DE LA VENTA
========================================== */

$stmtVenta = $conexion->prepare(
    "SELECT *
     FROM ventas
     WHERE id_venta = ?"
);

$stmtVenta->bind_param(
    "i",
    $idVenta
);

$stmtVenta->execute();

$resultadoVenta =
    $stmtVenta->get_result();


if ($resultadoVenta->num_rows !== 1) {

    die("La venta seleccionada no existe.");
}

$venta =
    $resultadoVenta->fetch_assoc();


/* ==========================================
   CONSULTAR PRODUCTOS DE LA VENTA
========================================== */

$stmtDetalle = $conexion->prepare(
    "SELECT
        dv.cantidad,
        dv.precio,
        p.nombre AS producto
     FROM detalle_ventas dv
     INNER JOIN productos p
        ON dv.id_producto = p.id_producto
     WHERE dv.id_venta = ?
     ORDER BY dv.id_detalle ASC"
);

$stmtDetalle->bind_param(
    "i",
    $idVenta
);

$stmtDetalle->execute();

$detalles =
    $stmtDetalle->get_result();

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
        Detalle de venta | My Smart POS
    </title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>

<body>

<div class="app">

    <!-- MENÚ -->

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

                <h1>
                    Venta #<?php echo $venta["id_venta"]; ?>
                </h1>

                <p>
                    Detalle de la operación realizada
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


        <!-- INFORMACIÓN GENERAL -->

        <div class="sale-detail-header">

            <div>

                <span>Venta</span>

                <strong>
                    #<?php echo $venta["id_venta"]; ?>
                </strong>

            </div>


            <div>

                <span>Fecha</span>

                <strong>

                    <?php

                    $fecha =
                        new DateTime(
                            $venta["fecha"]
                        );

                    echo $fecha->format(
                        "d/m/Y h:i A"
                    );

                    ?>

                </strong>

            </div>


            <div>

                <span>Total</span>

                <strong>

                    RD$
                    <?php
                    echo number_format(
                        $venta["total"],
                        2
                    );
                    ?>

                </strong>

            </div>

        </div>


        <!-- PRODUCTOS -->

        <div class="table-card">

            <div class="table-header">

                <div>

                    <h2>Productos vendidos</h2>

                    <p>
                        Productos incluidos en esta venta
                    </p>

                </div>

            </div>


            <div class="table-responsive">

                <table>

                    <thead>

                        <tr>

                            <th>Producto</th>

                            <th>Precio unitario</th>

                            <th>Cantidad</th>

                            <th>Subtotal</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php
                    while (
                        $detalle =
                        $detalles->fetch_assoc()
                    ):
                    ?>

                        <tr>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $detalle["producto"]
                                );
                                ?>

                            </td>


                            <td>

                                RD$

                                <?php
                                echo number_format(
                                    $detalle["precio"],
                                    2
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo $detalle["cantidad"];
                                ?>

                            </td>


                            <td>

                                <strong>

                                    RD$

                                    <?php

                                    $subtotal =
                                        $detalle["precio"]
                                        *
                                        $detalle["cantidad"];

                                    echo number_format(
                                        $subtotal,
                                        2
                                    );

                                    ?>

                                </strong>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>


            <div class="detail-total">

                <span>
                    Total pagado
                </span>

                <strong>

                    RD$

                    <?php
                    echo number_format(
                        $venta["total"],
                        2
                    );
                    ?>

                </strong>

            </div>


            <a
                href="historial.php"
                class="btn-back"
            >
                Volver al historial
            </a>

        </div>

    </main>

</div>

</body>

</html>