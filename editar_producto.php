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

/* =========================
   OBTENER ID DEL PRODUCTO
========================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Error: no se recibió un ID de producto válido.");
}

$id = intval($_GET["id"]);


/* =========================
   BUSCAR PRODUCTO
========================= */

$sql = "SELECT * FROM productos WHERE id_producto = ?";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conexion->error);
}

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {
    die("Error al buscar el producto: " . $stmt->error);
}

$resultado = $stmt->get_result();

if ($resultado->num_rows == 0) {
    die("Error: el producto seleccionado no existe.");
}

$producto = $resultado->fetch_assoc();


/* =========================
   ACTUALIZAR PRODUCTO
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"]);
    $categoria = trim($_POST["categoria"]);

    $precio = floatval($_POST["precio"]);
    $stock = intval($_POST["stock"]);
    $stock_minimo = intval($_POST["stock_minimo"]);


    if ($nombre === "") {

        $error = "El nombre del producto es obligatorio.";

    } elseif ($precio < 0 || $stock < 0 || $stock_minimo < 0) {

        $error = "Los valores no pueden ser negativos.";

    } else {

        $sqlActualizar = "
            UPDATE productos
            SET nombre = ?,
                categoria = ?,
                precio = ?,
                stock = ?,
                stock_minimo = ?
            WHERE id_producto = ?
        ";

        $stmtActualizar = $conexion->prepare($sqlActualizar);

        if (!$stmtActualizar) {
            die(
                "Error al preparar la actualización: "
                . $conexion->error
            );
        }

        $stmtActualizar->bind_param(
            "ssdiii",
            $nombre,
            $categoria,
            $precio,
            $stock,
            $stock_minimo,
            $id
        );

        if ($stmtActualizar->execute()) {

            header("Location: productos.php");
            exit;

        } else {

            $error = "No se pudo actualizar el producto: "
                   . $stmtActualizar->error;
        }
    }
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

    <title>Editar producto | My Smart POS</title>

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

            <a
                href="productos.php"
                class="active"
            >
                Productos
            </a>

            <a href="ventas.php">
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

                <h1>Editar producto</h1>

                <p>
                    Modificar información del producto
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


        <div class="edit-container">

            <div class="form-card">

                <h2>
                    <?php
                    echo htmlspecialchars(
                        $producto["nombre"]
                    );
                    ?>
                </h2>


                <?php if ($error != ""): ?>

                    <div class="error">

                        <?php
                        echo htmlspecialchars($error);
                        ?>

                    </div>

                <?php endif; ?>


                <form method="POST">


                    <!-- NOMBRE -->

                    <label for="nombre">
                        Nombre del producto *
                    </label>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        value="<?php
                            echo htmlspecialchars(
                                $producto["nombre"]
                            );
                        ?>"
                        required
                    >


                    <!-- CATEGORÍA -->

                    <label for="categoria">
                        Categoría
                    </label>

                    <input
                        type="text"
                        id="categoria"
                        name="categoria"
                        value="<?php
                            echo htmlspecialchars(
                                $producto["categoria"]
                            );
                        ?>"
                    >


                    <!-- PRECIO -->

                    <label for="precio">
                        Precio de venta *
                    </label>

                    <input
                        type="number"
                        id="precio"
                        name="precio"
                        min="0"
                        step="0.01"
                        value="<?php
                            echo $producto["precio"];
                        ?>"
                        required
                    >


                    <!-- STOCK -->

                    <label for="stock">
                        Cantidad disponible *
                    </label>

                    <input
                        type="number"
                        id="stock"
                        name="stock"
                        min="0"
                        value="<?php
                            echo $producto["stock"];
                        ?>"
                        required
                    >


                    <!-- STOCK MÍNIMO -->

                    <label for="stock_minimo">
                        Stock mínimo *
                    </label>

                    <input
                        type="number"
                        id="stock_minimo"
                        name="stock_minimo"
                        min="0"
                        value="<?php
                            echo $producto["stock_minimo"];
                        ?>"
                        required
                    >


                    <button type="submit">
                        Guardar cambios
                    </button>


                    <a
                        href="productos.php"
                        class="btn-cancel"
                    >
                        Cancelar
                    </a>

                </form>

            </div>

        </div>

    </main>

</div>

</body>

</html>