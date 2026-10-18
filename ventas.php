<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";

$error = "";
$mensaje = "";

/* ==========================================
   CREAR CARRITO EN LA SESIÓN
========================================== */

if (!isset($_SESSION["carrito"])) {
    $_SESSION["carrito"] = [];
}


/* ==========================================
   AGREGAR PRODUCTO AL CARRITO
========================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["accion"])
    && $_POST["accion"] === "agregar"
) {

    $id_producto = intval($_POST["id_producto"]);
    $cantidad = intval($_POST["cantidad"]);

    if ($id_producto <= 0 || $cantidad <= 0) {

        $error = "Seleccione un producto y una cantidad válida.";

    } else {

        $stmt = $conexion->prepare(
            "SELECT id_producto, nombre, precio, stock
             FROM productos
             WHERE id_producto = ?"
        );

        $stmt->bind_param("i", $id_producto);
        $stmt->execute();

        $resultadoProducto = $stmt->get_result();

        if ($resultadoProducto->num_rows !== 1) {

            $error = "El producto seleccionado no existe.";

        } else {

            $producto = $resultadoProducto->fetch_assoc();

            // Cantidad que ya existe en el carrito
            $cantidadActual = 0;

            if (isset($_SESSION["carrito"][$id_producto])) {
                $cantidadActual =
                    $_SESSION["carrito"][$id_producto]["cantidad"];
            }

            $cantidadTotal =
                $cantidadActual + $cantidad;

            // Comprobar stock
            if ($cantidadTotal > $producto["stock"]) {

                $error =
                    "No hay suficiente stock disponible para "
                    . $producto["nombre"] . ".";

            } else {

                $_SESSION["carrito"][$id_producto] = [
                    "id_producto" => $producto["id_producto"],
                    "nombre" => $producto["nombre"],
                    "precio" => $producto["precio"],
                    "cantidad" => $cantidadTotal
                ];

                $mensaje =
                    "Producto agregado a la venta.";
            }
        }
    }
}


/* ==========================================
   ELIMINAR PRODUCTO DEL CARRITO
========================================== */

if (isset($_GET["quitar"])) {

    $idQuitar = intval($_GET["quitar"]);

    if (isset($_SESSION["carrito"][$idQuitar])) {

        unset($_SESSION["carrito"][$idQuitar]);

        $mensaje =
            "Producto eliminado de la venta.";
    }
}


/* ==========================================
   VACIAR CARRITO
========================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["accion"])
    && $_POST["accion"] === "vaciar"
) {

    $_SESSION["carrito"] = [];

    $mensaje = "La venta fue vaciada.";
}


/* ==========================================
   REGISTRAR VENTA COMPLETA
========================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["accion"])
    && $_POST["accion"] === "registrar"
) {

    if (empty($_SESSION["carrito"])) {

        $error =
            "Debe agregar al menos un producto antes de registrar la venta.";

    } else {

        $totalVenta = 0;
        $stockValido = true;


        /*
         * Volvemos a comprobar cada producto
         * directamente en la base de datos.
         */

        foreach ($_SESSION["carrito"] as $item) {

            $idProducto =
                intval($item["id_producto"]);

            $cantidad =
                intval($item["cantidad"]);

            $stmtStock = $conexion->prepare(
                "SELECT nombre, precio, stock
                 FROM productos
                 WHERE id_producto = ?"
            );

            $stmtStock->bind_param(
                "i",
                $idProducto
            );

            $stmtStock->execute();

            $resultadoStock =
                $stmtStock->get_result();


            if ($resultadoStock->num_rows !== 1) {

                $error =
                    "Uno de los productos ya no está disponible.";

                $stockValido = false;

                break;
            }


            $productoActual =
                $resultadoStock->fetch_assoc();


            if (
                $productoActual["stock"]
                < $cantidad
            ) {

                $error =
                    "Stock insuficiente para "
                    . $productoActual["nombre"]
                    . ".";

                $stockValido = false;

                break;
            }


            /*
             * Utilizamos el precio actual
             * de la base de datos.
             */

            $_SESSION["carrito"]
                [$idProducto]["precio"] =
                $productoActual["precio"];


            $totalVenta +=
                $productoActual["precio"]
                * $cantidad;
        }


        if ($stockValido) {

            $conexion->begin_transaction();

            try {

                /* =========================
                   CREAR VENTA
                ========================== */

                $stmtVenta =
                    $conexion->prepare(
                        "INSERT INTO ventas (total)
                         VALUES (?)"
                    );

                if (!$stmtVenta) {
                    throw new Exception(
                        $conexion->error
                    );
                }

                $stmtVenta->bind_param(
                    "d",
                    $totalVenta
                );

                if (!$stmtVenta->execute()) {
                    throw new Exception(
                        $stmtVenta->error
                    );
                }


                $idVenta =
                    $conexion->insert_id;


                /* =========================
                   GUARDAR CADA PRODUCTO
                ========================== */

                foreach (
                    $_SESSION["carrito"]
                    as $item
                ) {

                    $idProducto =
                        intval(
                            $item["id_producto"]
                        );

                    $cantidad =
                        intval(
                            $item["cantidad"]
                        );

                    $precio =
                        floatval(
                            $item["precio"]
                        );


                    // Detalle de venta

                    $stmtDetalle =
                        $conexion->prepare(
                            "INSERT INTO detalle_ventas
                            (
                                id_venta,
                                id_producto,
                                cantidad,
                                precio
                            )
                            VALUES (?, ?, ?, ?)"
                        );

                    if (!$stmtDetalle) {
                        throw new Exception(
                            $conexion->error
                        );
                    }

                    $stmtDetalle->bind_param(
                        "iiid",
                        $idVenta,
                        $idProducto,
                        $cantidad,
                        $precio
                    );

                    if (!$stmtDetalle->execute()) {
                        throw new Exception(
                            $stmtDetalle->error
                        );
                    }


                    // Descontar inventario

                    $stmtActualizar =
                        $conexion->prepare(
                            "UPDATE productos
                             SET stock = stock - ?
                             WHERE id_producto = ?
                             AND stock >= ?"
                        );

                    if (!$stmtActualizar) {
                        throw new Exception(
                            $conexion->error
                        );
                    }

                    $stmtActualizar->bind_param(
                        "iii",
                        $cantidad,
                        $idProducto,
                        $cantidad
                    );

                    if (!$stmtActualizar->execute()) {
                        throw new Exception(
                            $stmtActualizar->error
                        );
                    }

                    if (
                        $stmtActualizar->affected_rows
                        !== 1
                    ) {

                        throw new Exception(
                            "No fue posible actualizar el inventario."
                        );
                    }
                }


                /* =========================
                   CONFIRMAR TODO
                ========================== */

                $conexion->commit();

                $_SESSION["carrito"] = [];

                $mensaje =
                    "Venta #"
                    . $idVenta
                    . " registrada correctamente. "
                    . "Total: RD$ "
                    . number_format(
                        $totalVenta,
                        2
                    );


            } catch (Throwable $e) {

                $conexion->rollback();

                $error =
                    "No se pudo registrar la venta: "
                    . $e->getMessage();
            }
        }
    }
}


/* ==========================================
   CALCULAR TOTAL DEL CARRITO
========================================== */

$totalCarrito = 0;

foreach ($_SESSION["carrito"] as $item) {

    $totalCarrito +=
        $item["precio"]
        * $item["cantidad"];
}


/* ==========================================
   CONSULTAR PRODUCTOS DISPONIBLES
========================================== */

$productos = $conexion->query(
    "SELECT *
     FROM productos
     WHERE stock > 0
     ORDER BY nombre ASC"
);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Nueva venta | My Smart POS</title>

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

            <a
                href="ventas.php"
                class="active"
            >
                Nueva venta
            </a>

            <a href="historial.php">
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

                <h1>Nueva venta</h1>

                <p>
                    Agregue uno o varios productos
                    a la venta
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


        <!-- MENSAJES -->

        <?php if ($mensaje != ""): ?>

            <div class="success">

                <?php
                echo htmlspecialchars($mensaje);
                ?>

            </div>

        <?php endif; ?>


        <?php if ($error != ""): ?>

            <div class="error">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <section class="pos-layout">


            <!-- AGREGAR PRODUCTOS -->

            <div class="form-card">

                <h2>Agregar producto</h2>

                <p class="form-description">
                    Seleccione un producto
                    y la cantidad.
                </p>


                <?php if ($productos->num_rows > 0): ?>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="accion"
                            value="agregar"
                        >


                        <label for="id_producto">
                            Producto *
                        </label>


                        <select
                            id="id_producto"
                            name="id_producto"
                            required
                        >

                            <option value="">
                                Seleccione un producto
                            </option>


                            <?php
                            while (
                                $producto =
                                $productos->fetch_assoc()
                            ):
                            ?>

                                <option
                                    value="<?php
                                    echo $producto["id_producto"];
                                    ?>"
                                    data-precio="<?php
                                    echo $producto["precio"];
                                    ?>"
                                    data-stock="<?php
                                    echo $producto["stock"];
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $producto["nombre"]
                                    );
                                    ?>

                                    - RD$

                                    <?php
                                    echo number_format(
                                        $producto["precio"],
                                        2
                                    );
                                    ?>

                                    (Stock:
                                    <?php
                                    echo $producto["stock"];
                                    ?>
                                    )

                                </option>

                            <?php endwhile; ?>

                        </select>


                        <div class="product-info">

                            <div>

                                <span>Precio</span>

                                <strong id="precioProducto">
                                    RD$ 0.00
                                </strong>

                            </div>


                            <div>

                                <span>Disponible</span>

                                <strong id="stockProducto">
                                    0
                                </strong>

                            </div>

                        </div>


                        <label for="cantidad">
                            Cantidad *
                        </label>

                        <input
                            type="number"
                            id="cantidad"
                            name="cantidad"
                            min="1"
                            value="1"
                            required
                        >


                        <div class="sale-total">

                            <span>Subtotal</span>

                            <strong id="subtotal">
                                RD$ 0.00
                            </strong>

                        </div>


                        <button type="submit">
                            Agregar a la venta
                        </button>

                    </form>

                <?php else: ?>

                    <div class="empty">

                        No hay productos
                        con stock disponible.

                    </div>

                <?php endif; ?>

            </div>


            <!-- CARRITO -->

            <div class="table-card">

                <div class="table-header">

                    <div>

                        <h2>Productos de la venta</h2>

                        <p>
                            Detalle de la operación actual
                        </p>

                    </div>

                </div>


                <div class="table-responsive">

                    <table>

                        <thead>

                            <tr>

                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Cantidad</th>
                                <th>Subtotal</th>
                                <th></th>

                            </tr>

                        </thead>

                        <tbody>


                        <?php
                        if (!empty($_SESSION["carrito"])):
                        ?>


                            <?php
                            foreach (
                                $_SESSION["carrito"]
                                as $item
                            ):
                            ?>

                                <tr>

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $item["nombre"]
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        RD$
                                        <?php
                                        echo number_format(
                                            $item["precio"],
                                            2
                                        );
                                        ?>

                                    </td>


                                    <td>

                                        <?php
                                        echo $item["cantidad"];
                                        ?>

                                    </td>


                                    <td>

                                        <strong>

                                            RD$
                                            <?php
                                            echo number_format(
                                                $item["precio"]
                                                *
                                                $item["cantidad"],
                                                2
                                            );
                                            ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <a
                                            href="ventas.php?quitar=<?php
                                            echo $item["id_producto"];
                                            ?>"
                                            class="btn-delete"
                                        >
                                            Quitar
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="5"
                                    class="empty"
                                >

                                    Todavía no hay productos
                                    agregados a la venta.

                                </td>

                            </tr>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>


                <!-- TOTAL -->

                <div class="cart-summary">

                    <span>
                        Total de la venta
                    </span>

                    <strong>
                        RD$
                        <?php
                        echo number_format(
                            $totalCarrito,
                            2
                        );
                        ?>
                    </strong>

                </div>


                <?php
                if (!empty($_SESSION["carrito"])):
                ?>

                    <div class="cart-actions">


                        <!-- VACIAR -->

                        <form method="POST">

                            <input
                                type="hidden"
                                name="accion"
                                value="vaciar"
                            >

                            <button
                                type="submit"
                                class="btn-clear"
                                onclick="return confirm('¿Desea vaciar la venta?');"
                            >
                                Vaciar
                            </button>

                        </form>


                        <!-- REGISTRAR -->

                        <form method="POST">

                            <input
                                type="hidden"
                                name="accion"
                                value="registrar"
                            >

                            <button
                                type="submit"
                                class="btn-register-sale"
                            >
                                Registrar venta
                            </button>

                        </form>


                    </div>

                <?php endif; ?>


            </div>

        </section>

    </main>

</div>


<script>

const producto =
    document.getElementById("id_producto");

const cantidad =
    document.getElementById("cantidad");

const precioTexto =
    document.getElementById("precioProducto");

const stockTexto =
    document.getElementById("stockProducto");

const subtotalTexto =
    document.getElementById("subtotal");


function actualizarDatos() {

    if (!producto) {
        return;
    }

    const opcion =
        producto.options[
            producto.selectedIndex
        ];

    const precio =
        parseFloat(
            opcion.dataset.precio
        ) || 0;

    const stock =
        parseInt(
            opcion.dataset.stock
        ) || 0;

    const cantidadSeleccionada =
        parseInt(
            cantidad.value
        ) || 0;


    precioTexto.textContent =
        "RD$ " + precio.toFixed(2);

    stockTexto.textContent =
        stock;

    subtotalTexto.textContent =
        "RD$ "
        +
        (
            precio
            *
            cantidadSeleccionada
        ).toFixed(2);


    if (stock > 0) {
        cantidad.max = stock;
    }
}


if (producto) {

    producto.addEventListener(
        "change",
        actualizarDatos
    );

    cantidad.addEventListener(
        "input",
        actualizarDatos
    );
}

</script>

</body>

</html>