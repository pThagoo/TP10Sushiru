

<?php
include 'db.php'; // Conexión a la base de datos

session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] != 'mesero') {
    // Si el usuario no está autenticado o no es mesero, redirigir al login o a la página anterior
    header("Location: inicioADMIN.php");
    exit();
}

if (isset($_POST['confirmar_pedido'])) {
    $id_mesa = $_POST['id_mesa'];
    $platos = $_POST['platos']; // Array con IDs de los platos y cantidades
    
    // Crear un nuevo pedido
    $insertPedido = "INSERT INTO pedidos (id_mesa, estado) VALUES ($id_mesa, 'pendiente')";
    $conn->query($insertPedido);
    $id_pedido = $conn->insert_id;
    
    // Recorrer los platos seleccionados
    foreach ($platos as $id_plato => $cantidad) {
        // Insertar en detalle_pedido
        $insertDetalle = "INSERT INTO detalle_pedido (id_pedido, id_plato, cantidad) VALUES ($id_pedido, $id_plato, $cantidad)";
        $conn->query($insertDetalle);
        
        // Obtener los ingredientes del plato
        $ingredientesQuery = "SELECT id_ingrediente, cantidad FROM IngredientesPlato WHERE id_plato = $id_plato";
        $ingredientesResult = $conn->query($ingredientesQuery);
        
        while ($ingrediente = $ingredientesResult->fetch_assoc()) {
            $id_ingrediente = $ingrediente['id_ingrediente'];
            $cantidad_usada = $ingrediente['cantidad'] * $cantidad;
            
            // Verificar si hay suficiente stock antes de descontar
            $stockQuery = "SELECT stock FROM Ingredientes WHERE id_ingrediente = $id_ingrediente";
            $stockResult = $conn->query($stockQuery);
            $stockData = $stockResult->fetch_assoc();
            
            if ($stockData['stock'] >= $cantidad_usada) {
                // Descontar el stock del ingrediente
                $updateStock = "UPDATE Ingredientes SET stock = stock - $cantidad_usada WHERE id_ingrediente = $id_ingrediente";
                $conn->query($updateStock);
            } else {
                echo "No hay suficiente stock para el ingrediente ID: $id_ingrediente.";
                // Aquí podrías manejar el error mostrando un mensaje al usuario
            }
        }
        
    }

    echo "Pedido realizado con éxito. El stock ha sido actualizado.";
}
 
// Obtener mesas disponibles
$mesasQuery = "SELECT * FROM Mesas";
$mesasResult = $conn->query($mesasQuery);

// Obtener platos disponibles
$platosQuery = "SELECT * FROM Platos";
$platosResult = $conn->query($platosQuery);

// Procesar el pedido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_mesa = $_POST['id_mesa'];
    $platos = $_POST['platos']; // Array de platos seleccionados
    $cantidades = $_POST['quantity']; // Array de cantidades de platos

    // Verificar si la mesa está libre
    $mesaQuery = "SELECT estado FROM Mesas WHERE id_mesa=$id_mesa";
    $mesaResult = $conn->query($mesaQuery);
    $mesa = $mesaResult->fetch_assoc();

    if ($mesa['estado'] == 'ocupada') {
        echo "<p style='color:red;'>La mesa está ocupada. No se puede realizar el pedido.</p>";
    } else {
        // Insertar el pedido en la tabla pedidos
        $insertPedido = "INSERT INTO Pedidos (id_mesa, fecha_hora, estado) VALUES ($id_mesa, NOW(), 'pendiente')";
        if ($conn->query($insertPedido) === TRUE) {
            $id_pedido = $conn->insert_id; // Obtener el ID del pedido insertado

            // Insertar cada plato en la tabla detalle_pedido
            foreach ($platos as $index => $id_plato) {
                $cantidad = intval($cantidades[$index]);

                if ($cantidad > 0) { // Solo insertar si la cantidad es mayor a 0

                    // Verificar si el plato existe en la tabla Platos
                    $platoExisteQuery = "SELECT id_plato FROM Platos WHERE id_plato = $id_plato";
                    $platoExisteResult = $conn->query($platoExisteQuery);

                    if ($platoExisteResult->num_rows > 0) {
                        $insertDetalle = "INSERT INTO detalle_pedido (id_pedido, id_plato, cantidad) VALUES ($id_pedido, $id_plato, $cantidad)";
                        $conn->query($insertDetalle);
                    } else {
                        echo "<p style='color:red;'>Error: El plato con ID $id_plato no existe.</p>";
                    }
                }
            }

            // Actualizar el estado de la mesa a "ocupada"
            $updateMesa = "UPDATE Mesas SET estado='ocupada' WHERE id_mesa=$id_mesa";
            $conn->query($updateMesa);

            echo "<p style='color:green;'>Pedido realizado con éxito. La mesa ahora está ocupada.</p>";
        } else {
            echo "Error al realizar el pedido: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Realizar Pedido - Restaurante de Sushi</title>
    <link rel="stylesheet" href="../css/mesero.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <h2>Realizar Pedido</h2>
    <form method="POST" action="">
        <label for="mesa">Seleccionar Mesa:</label>
        <select name="id_mesa" required>
            <?php while ($mesa = $mesasResult->fetch_assoc()): ?>
                <option value="<?= $mesa['id_mesa']; ?>"><?= $mesa['numero_mesa']; ?> - <?= $mesa['estado']; ?></option>
            <?php endwhile; ?>
        </select>

        <h3>Seleccionar Platos:</h3>
        <div id="platos-container">
            <!-- Aquí se agregarán los platos dinámicamente -->
        </div>

        <div class="d-grid gap-2">
            <button class="btn btn-primary" id="add-dish" type="button">Agregar Producto</button>
            <button class="btn btn-success"  id="Enviar Pedido" type="submit">Enviar Pedido</button>
        </div>

    </form>

    <script>
        // Lista de platos disponibles
        const platos = <?php echo json_encode($platosResult->fetch_all(MYSQLI_ASSOC)); ?>;

        function addDish() {
            const container = document.getElementById('platos-container');
            const div = document.createElement('div');
            div.classList.add('plato');

            const select = document.createElement('select');
            select.classList.add('dish-select');
            select.name = 'platos[]';

            // Añadir opción vacía
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = 'Seleccionar Plato';
            select.appendChild(emptyOption);

            // Añadir platos
            platos.forEach(plato => {
                const option = document.createElement('option');
                option.value = plato.id_plato;
                option.text = plato.nombre;
                select.appendChild(option);
            });

            const quantityDiv = document.createElement('div');
            quantityDiv.classList.add('cantidad-controls');
            
            const minusButton = document.createElement('button');
            minusButton.type = 'button';
            minusButton.classList.add('cantidad-btn');
            minusButton.textContent = '-';
            minusButton.onclick = function() {
                adjustQuantity(quantityInput, -1);
            };

            const quantityInput = document.createElement('input');
            quantityInput.type = 'number';
            quantityInput.classList.add('cantidad-input');
            quantityInput.name = 'quantity[]';
            quantityInput.value = '0';
            quantityInput.min = '0';

            const plusButton = document.createElement('button');
            plusButton.type = 'button';
            plusButton.classList.add('cantidad-btn');
            plusButton.textContent = '+';
            plusButton.onclick = function() {
                adjustQuantity(quantityInput, 1);
            };

            quantityDiv.appendChild(minusButton);
            quantityDiv.appendChild(quantityInput);
            quantityDiv.appendChild(plusButton);

            div.appendChild(select);
            div.appendChild(quantityDiv);
            container.appendChild(div);
        }

        function adjustQuantity(input, change) {
            let currentValue = parseInt(input.value, 10);
            currentValue = isNaN(currentValue) ? 0 : currentValue;
            currentValue += change;
            if (currentValue < 0) currentValue = 0;
            input.value = currentValue;
        }

        document.getElementById('add-dish').addEventListener('click', addDish);
    </script>
</body>
</html>
