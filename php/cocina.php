<?php
include 'db.php'; // Conexión a la base de datos

session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] != 'cocinero') {
    header("Location: inicioADMIN.php");
    exit();
}

// Procesar la solicitud de añadir o quitar stock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si las claves existen en el array $_POST
    if (isset($_POST['id_ingrediente'], $_POST['cantidad'], $_POST['accion'])) {
        $id_ingrediente = $_POST['id_ingrediente'];
        $cantidad = (int)$_POST['cantidad'];
        $accion = $_POST['accion']; // 'añadir' o 'quitar'

        if ($cantidad <= 0) {
            die('La cantidad debe ser un número positivo');
        }

        // Validar y ajustar stock
        if ($accion === 'añadir') {
            $updateQuery = "UPDATE ingredientes SET stock = stock + ? WHERE id_ingrediente = ?";
        } elseif ($accion === 'quitar') {
            $currentStockQuery = "SELECT stock FROM ingredientes WHERE id_ingrediente = ?";
            $stmt = $conn->prepare($currentStockQuery);
            $stmt->bind_param('i', $id_ingrediente);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentStock = $result->fetch_assoc()['stock'];

            if ($cantidad > $currentStock) {
                die('No hay suficiente stock para quitar');  
            }

            $updateQuery = "UPDATE ingredientes SET stock = stock - ? WHERE id_ingrediente = ?";
        } else {
            die('Acción no válida');
        }  

        $stmt = $conn->prepare($updateQuery);
        if ($stmt === false) {
            die('Error en la preparación de la consulta');
        }

        $stmt->bind_param('ii', $cantidad, $id_ingrediente);
        $stmt->execute();
        if ($stmt->error) {
            die('Error al ejecutar la consulta: ' . $stmt->error);
        }

        header("Location: cocina.php?success=1");
        exit();
    } elseif (isset($_POST['id_pedido'], $_POST['accion_pedido']) && $_POST['accion_pedido'] === 'realizado') {
        // Procesar la eliminación del pedido
        $id_pedido = $_POST['id_pedido'];

        // Obtener los detalles del pedido (id_plato y cantidad)
        $detallePedidoQuery = "SELECT id_plato, cantidad FROM detalle_pedido WHERE id_pedido = ?";
        $stmt = $conn->prepare($detallePedidoQuery);
        $stmt->bind_param('i', $id_pedido);
        $stmt->execute();
        $detalles = $stmt->get_result();

        // Actualizar el stock de ingredientes para cada plato
        while ($detalle = $detalles->fetch_assoc()) {
            $id_plato = $detalle['id_plato'];
            $cantidad_pedido = $detalle['cantidad'];

            // Obtener los ingredientes necesarios para el plato
            $ingredientesPlatoQuery = "SELECT id_ingrediente, cantidad FROM IngredientesPlato WHERE id_plato = ?";
            $stmtIngredientes = $conn->prepare($ingredientesPlatoQuery);
            $stmtIngredientes->bind_param('i', $id_plato);
            $stmtIngredientes->execute();
            $ingredientes = $stmtIngredientes->get_result();

            // Restar la cantidad de cada ingrediente del stock
            while ($ingrediente = $ingredientes->fetch_assoc()) {
                $id_ingrediente = $ingrediente['id_ingrediente'];
                $cantidad_ingrediente = $ingrediente['cantidad'] * $cantidad_pedido;

                $updateStockQuery = "UPDATE ingredientes SET stock = stock - ? WHERE id_ingrediente = ?";
                $stmtUpdateStock = $conn->prepare($updateStockQuery);
                $stmtUpdateStock->bind_param('ii', $cantidad_ingrediente, $id_ingrediente);
                $stmtUpdateStock->execute();

                if ($stmtUpdateStock->error) {
                    die('Error al actualizar el stock: ' . $stmtUpdateStock->error);
                }
            }
        }

        // Luego eliminar los detalles del pedido
        $deleteDetailsQuery = "DELETE FROM detalle_pedido WHERE id_pedido = ?";
        $stmt = $conn->prepare($deleteDetailsQuery);
        if ($stmt === false) {
            die('Error en la preparación de la consulta de detalles');
        }

        $stmt->bind_param('i', $id_pedido);
        $stmt->execute();
        if ($stmt->error) {
            die('Error al eliminar los detalles del pedido: ' . $stmt->error);
        }

        // Luego eliminar el pedido
        $deleteQuery = "DELETE FROM pedidos WHERE id_pedido = ?";
        $stmt = $conn->prepare($deleteQuery);
        if ($stmt === false) {
            die('Error en la preparación de la consulta de pedidos');
        }

        $stmt->bind_param('i', $id_pedido);
        $stmt->execute();
        if ($stmt->error) {
            die('Error al ejecutar la consulta de eliminación del pedido: ' . $stmt->error);
        }

        header("Location: cocina.php?success=pedido_eliminado");
        exit();
    } elseif (isset($_POST['id_pedido'], $_POST['accion_pedido']) && $_POST['accion_pedido'] === 'cancelar') {
        // Procesar la acción de cancelar el pedido
        $id_pedido = $_POST['id_pedido'];

        // Primero eliminar los detalles del pedido
        $deleteDetailsQuery = "DELETE FROM detalle_pedido WHERE id_pedido = ?";
        $stmt = $conn->prepare($deleteDetailsQuery);
        if ($stmt === false) {
            die('Error en la preparación de la consulta de detalles');
        }

        $stmt->bind_param('i', $id_pedido);
        $stmt->execute();
        if ($stmt->error) {
            die('Error al eliminar los detalles del pedido: ' . $stmt->error);
        }

        // Luego eliminar el pedido
        $deleteQuery = "DELETE FROM pedidos WHERE id_pedido = ?";
        $stmt = $conn->prepare($deleteQuery);
        if ($stmt === false) {
            die('Error en la preparación de la consulta de pedidos');
        }

        $stmt->bind_param('i', $id_pedido);
        $stmt->execute();
        if ($stmt->error) {
            die('Error al ejecutar la consulta de eliminación del pedido: ' . $stmt->error);
        }

        header("Location: cocina.php?success=pedido_cancelado");
        exit();
    } elseif (isset($_POST['nombre_plato'], $_POST['descripcion'], $_POST['precio'])) {
        // Aquí deberías agregar la lógica para insertar un nuevo plato
        $nombre_plato = $_POST['nombre_plato'];
        $descripcion = $_POST['descripcion'];
        $precio = (float)$_POST['precio'];

        $insertQuery = "INSERT INTO platos (nombre, descripcion, precio) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        if ($stmt === false) {
            die('Error en la preparación de la consulta de inserción');
        }

        $stmt->bind_param('ssd', $nombre_plato, $descripcion, $precio);
        $stmt->execute();
        if ($stmt->error) {
            echo "Error al agregar el plato: " . $conn->error;
        } else {
            header("Location: agregarPLATOS.php?success=1");
            exit();
        }
    } else {
        die('Datos del formulario no válidos');
    }
}

// Obtener los pedidos y detalles
$pedidosQuery = "
    SELECT p.id_pedido, m.numero_mesa AS mesa, pl.nombre AS plato, pl.descripcion, pl.precio, d.cantidad, p.fecha_hora, p.estado, d.id_plato
    FROM pedidos p
    JOIN mesas m ON p.id_mesa = m.id_mesa
    JOIN detalle_pedido d ON p.id_pedido = d.id_pedido
    JOIN platos pl ON d.id_plato = pl.id_plato
";
$pedidosResult = $conn->query($pedidosQuery);

// Obtener todos los ingredientes y su stock
$ingredientesQuery = "SELECT id_ingrediente, nombre, stock FROM ingredientes";
$ingredientesResult = $conn->query($ingredientesQuery);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedidos y Stock - Cocina</title>
    <link rel="stylesheet" href="../css/cocina.css">
</head>
<body>
    <h2>Pedidos Pendientes</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ID Pedido</th>
                <th>Mesa</th>
                <th>Plato</th>
                <th>Cantidad</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Ingredientes</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($pedido = $pedidosResult->fetch_assoc()): ?>
                <?php
                // Obtener ingredientes para cada plato en el pedido
                $ingredientesPedidoQuery = "
                    SELECT i.nombre AS ingrediente, ip.cantidad
                    FROM IngredientesPlato ip
                    JOIN Ingredientes i ON ip.id_ingrediente = i.id_ingrediente
                    WHERE ip.id_plato = {$pedido['id_plato']}
                ";
                $ingredientesPedidoResult = $conn->query($ingredientesPedidoQuery);
                $ingredientes = [];
                while ($ingrediente = $ingredientesPedidoResult->fetch_assoc()) {
                    $ingredientes[] = $ingrediente['ingrediente'] . ' (' . $ingrediente['cantidad'] . ')';
                }
                ?>
                <tr>
                    <td><?= $pedido['id_pedido']; ?></td>
                    <td><?= $pedido['mesa']; ?></td>
                    <td><?= $pedido['plato']; ?></td>
                    <td><?= $pedido['cantidad']; ?></td>
                    <td><?= $pedido['descripcion']; ?></td>
                    <td><?= $pedido['precio']; ?></td>
                    <td><?= implode('<br>', $ingredientes); ?></td>
                    <td>
                        <form action="" method="POST" style="display: inline;">
                            <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido']; ?>">
                            <button type="submit" name="accion_pedido" value="realizado">Marcar como Realizado</button>
                        </form>
                        <form action="" method="POST" style="display: inline;">
                            <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido']; ?>">
                            <button type="submit" name="accion_pedido" value="cancelar">Cancelar</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
   
    <h2>Stock de Ingredientes</h2>
    <table border="1">
        <thead>
            <tr>
                <th>ID Ingrediente</th>
                <th>Nombre</th>
                <th>Stock</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($ingrediente = $ingredientesResult->fetch_assoc()): ?>
                <tr>
                    <td><?= $ingrediente['id_ingrediente']; ?></td>
                    <td><?= $ingrediente['nombre']; ?></td>
                    <td><?= $ingrediente['stock']; ?></td>
                    <td>
                        <form action="" method="POST">
                            <input type="hidden" name="id_ingrediente" value="<?= $ingrediente['id_ingrediente']; ?>">
                            <input type="number" name="cantidad" value="0" min="0">
                            <select name="accion">
                                <option value="añadir">Añadir</option>
                                <option value="quitar">Quitar</option>
                            </select>
                            <button type="submit">Actualizar Stock</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php
    if (isset($_GET['success'])) {
        echo '<p>Operación realizada con éxito.</p>';
    }
    ?>
</body>
</html>
