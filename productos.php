<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";

$mensaje = "";
$error = "";

// REGISTRAR PRODUCTO
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"]);
    $categoria = trim($_POST["categoria"]);
    $precio = $_POST["precio"];
    $stock = $_POST["stock"];
    $stock_minimo = $_POST["stock_minimo"];

    if ($nombre == "" || $precio === "" || $stock === "") {

        $error = "Complete los campos obligatorios.";

    } elseif ($precio < 0 || $stock < 0 || $stock_minimo < 0) {

        $error = "Los valores no pueden ser negativos.";

    } else {

        $sql = "INSERT INTO productos
                (nombre, categoria, precio, stock, stock_minimo)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        $stmt->bind_param(
            "ssdii",
            $nombre,
            $categoria,
            $precio,
            $stock,
            $stock_minimo
        );

        if ($stmt->execute()) {
            $mensaje = "Producto registrado correctamente.";
        } else {
            $error = "No se pudo registrar el producto.";
        }
    }
}

// CONSULTAR PRODUCTOS
$resultado = $conexion->query(
    "SELECT * FROM productos ORDER BY id_producto DESC"
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

    <title>Productos | My Smart POS</title>

    <link rel="stylesheet" href="css/style.css">

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

            <a href="productos.php" class="active">
                Productos
            </a>

            <a href="ventas.php">
                Nueva venta
            </a>

            <a href="historial.php">
                Historial de ventas
            </a>

        </nav>

        <a href="logout.php" class="logout">
            Cerrar sesión
        </a>

    </aside>


    <!-- CONTENIDO PRINCIPAL -->

    <main class="main-content">

        <header class="topbar">

            <div>

                <h1>Productos</h1>

                <p>
                    Administración de productos e inventario
                </p>

            </div>

            <div class="user-info">

                <span>Usuario</span>

                <strong>
                    <?php
                    echo htmlspecialchars($_SESSION["nombre"]);
                    ?>
                </strong>

            </div>

        </header>


        <!-- MENSAJE DE ÉXITO -->

        <?php if ($mensaje): ?>

            <div class="success">

                <?php
                echo htmlspecialchars($mensaje);
                ?>

            </div>

        <?php endif; ?>


        <!-- MENSAJE DE ERROR -->

        <?php if ($error): ?>

            <div class="error">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <section class="product-layout">


            <!-- FORMULARIO PARA REGISTRAR -->

            <div class="form-card">

                <h2>Registrar producto</h2>

                <form method="POST">

                    <label for="nombre">
                        Nombre del producto *
                    </label>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        placeholder="Ej. Coca-Cola 2L"
                        required
                    >


                    <label for="categoria">
                        Categoría
                    </label>

                    <input
                        type="text"
                        id="categoria"
                        name="categoria"
                        placeholder="Ej. Bebidas"
                    >


                    <label for="precio">
                        Precio de venta *
                    </label>

                    <input
                        type="number"
                        id="precio"
                        name="precio"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        required
                    >


                    <label for="stock">
                        Cantidad disponible *
                    </label>

                    <input
                        type="number"
                        id="stock"
                        name="stock"
                        min="0"
                        placeholder="0"
                        required
                    >


                    <label for="stock_minimo">
                        Stock mínimo
                    </label>

                    <input
                        type="number"
                        id="stock_minimo"
                        name="stock_minimo"
                        min="0"
                        value="5"
                        required
                    >


                    <button type="submit">
                        Guardar producto
                    </button>

                </form>

            </div>


            <!-- TABLA DE PRODUCTOS -->

            <div class="table-card">

                <div class="table-header">

                    <div>

                        <h2>Productos registrados</h2>

                        <p>
                            Productos disponibles en el sistema
                        </p>

                    </div>

                </div>


                <div class="table-responsive">

                    <table>

                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>Producto</th>

                                <th>Categoría</th>

                                <th>Precio</th>

                                <th>Stock</th>

                                <th>Estado</th>

                                <th>Acciones</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if ($resultado->num_rows > 0): ?>


                            <?php
                            while ($producto = $resultado->fetch_assoc()):
                            ?>

                                <tr>

                                    <!-- ID -->

                                    <td>

                                        <?php
                                        echo $producto["id_producto"];
                                        ?>

                                    </td>


                                    <!-- PRODUCTO -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $producto["nombre"]
                                        );
                                        ?>

                                    </td>


                                    <!-- CATEGORÍA -->

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $producto["categoria"]
                                        );
                                        ?>

                                    </td>


                                    <!-- PRECIO -->

                                    <td>

                                        RD$
                                        <?php
                                        echo number_format(
                                            $producto["precio"],
                                            2
                                        );
                                        ?>

                                    </td>


                                    <!-- STOCK -->

                                    <td>

                                        <?php
                                        echo $producto["stock"];
                                        ?>

                                    </td>


                                    <!-- ESTADO -->

                                    <td>

                                        <?php
                                        if (
                                            $producto["stock"]
                                            <=
                                            $producto["stock_minimo"]
                                        ):
                                        ?>

                                            <span class="badge low">
                                                Stock bajo
                                            </span>

                                        <?php else: ?>

                                            <span class="badge available">
                                                Disponible
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ACCIONES -->

                                    <td class="actions">

                                        <a
                                            href="editar_producto.php?id=<?php echo $producto["id_producto"]; ?>"
                                            class="btn-edit"
                                        >
                                            Editar
                                        </a>

                                        <a
                                            href="eliminar_producto.php?id=<?php echo $producto["id_producto"]; ?>"
                                            class="btn-delete"
                                            onclick="return confirm('¿Está seguro de eliminar este producto?');"
                                        >
                                            Eliminar
                                        </a>

                                    </td>

                                </tr>


                            <?php endwhile; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="7"
                                    class="empty"
                                >
                                    No hay productos registrados.
                                </td>

                            </tr>


                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>

    </main>

</div>

</body>

</html>