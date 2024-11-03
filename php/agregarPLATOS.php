<?php
include 'db.php'; // Conexión a la base de datos

session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] != 'admin') {
    // Si el usuario no está autenticado o no es admin, redirigir al login o a la página anterior
    header("Location: inicioADMIN.php");
    exit();
}

// Obtener los ingredientes disponibles
$ingredientesQuery = "SELECT * FROM Ingredientes";
$ingredientesResult = $conn->query($ingredientesQuery);

// Procesar la creación de un nuevo plato
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];

    // Insertar el nuevo plato en la base de datos
    $insertPlatoQuery = "INSERT INTO platos (nombre, descripcion, precio) VALUES ('$nombre', '$descripcion', $precio)";
    
    if ($conn->query($insertPlatoQuery) === TRUE) {
        $id_plato_nuevo = $conn->insert_id; // Obtener el ID del plato recién creado
        
        // Insertar los ingredientes seleccionados para el nuevo plato
        if (!empty($_POST['ingredientes']) && !empty($_POST['cantidades'])) {
            foreach ($_POST['ingredientes'] as $index => $id_ingrediente) {
                $cantidad = $_POST['cantidades'][$index];
                $insertIngredientePlatoQuery = "INSERT INTO IngredientesPlato (id_plato, id_ingrediente, cantidad) VALUES ($id_plato_nuevo, $id_ingrediente, '$cantidad')";
                $conn->query($insertIngredientePlatoQuery);
            }
        }

        // Insertar los nuevos ingredientes, si se han agregado
        if (!empty($_POST['nuevos_ingredientes']) && !empty($_POST['cantidades_nuevos'])) {
            foreach ($_POST['nuevos_ingredientes'] as $index => $nuevo_ingrediente) {
                $cantidad_nuevo = $_POST['cantidades_nuevos'][$index];
                if (!empty($nuevo_ingrediente)) {
                    // Insertar el nuevo ingrediente
                    $insertNuevoIngredienteQuery = "INSERT INTO Ingredientes (nombre) VALUES ('$nuevo_ingrediente')";
                    if ($conn->query($insertNuevoIngredienteQuery) === TRUE) {
                        $id_nuevo_ingrediente = $conn->insert_id;
                        // Asociar el nuevo ingrediente al plato
                        $insertIngredientePlatoQuery = "INSERT INTO IngredientesPlato (id_plato, id_ingrediente, cantidad) VALUES ($id_plato_nuevo, $id_nuevo_ingrediente, '$cantidad_nuevo')";
                        $conn->query($insertIngredientePlatoQuery);
                    }
                }
            }
        }

        // Redirigir para evitar reenvío del formulario
        header("Location: agregarPLATOS.php?success=1");
        exit();
    } else {
        echo "Error al agregar el plato: " . $conn->error;
    }
}

// Obtener los platos existentes
$platosQuery = "SELECT * FROM Platos";
$platosResult = $conn->query($platosQuery);

// Procesar la eliminación de un plato
if (isset($_GET['delete_id'])) {
    $id_plato = intval($_GET['delete_id']);

    // Eliminar ingredientes asociados
    $deleteIngredientesPlato = "DELETE FROM IngredientesPlato WHERE id_plato = $id_plato";
    $conn->query($deleteIngredientesPlato);

    // Eliminar plato
    $deletePlato = "DELETE FROM platos WHERE id_plato = $id_plato";
    if ($conn->query($deletePlato) === TRUE) {
        header("Location: agregarPLATOS.php?deleted=1");
        exit();
    } else {
        echo "Error al eliminar el plato: " . $conn->error;
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Plato - Restaurante de Sushi</title>
   <link rel="stylesheet" href="../css/agregarPLATOS.css">
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <h2>Agregar Nuevo Plato</h2>
    <form method="POST" action="">
        <label for="nombre">Nombre del Plato:</label>
        <input type="text" name="nombre" required>

        <label for="descripcion">Descripción:</label>
        <textarea name="descripcion" required></textarea>

        <label for="precio">Precio:</label>
        <input type="number" step="0.01" name="precio" required>

        <h3>Seleccionar Ingredientes Existentes</h3>
        <?php while ($ingrediente = $ingredientesResult->fetch_assoc()): ?>
            <div>
                <input type="checkbox" name="ingredientes[]" value="<?= $ingrediente['id_ingrediente']; ?>">
                <label><?= $ingrediente['nombre']; ?></label>
                <input type="text" name="cantidades[]" placeholder="Cantidad (e.g., 100g, 1 pieza)">
            </div>
        <?php endwhile; ?>

        <h3>Agregar Nuevos Ingredientes</h3>
        <div>
            <label for="nuevo_ingrediente">Nuevo Ingrediente:</label>
            <input type="text" name="nuevos_ingredientes[]" placeholder="Nombre del Ingrediente">
            <input type="text" name="cantidades_nuevos[]" placeholder="Cantidad (e.g., 100g, 1 pieza)">
        </div>
        <button type="button" onclick="agregarIngrediente()">Agregar otro ingrediente</button>

        <input type="submit" value="Agregar Plato">
    </form>

    <h2>Platos Existentes</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th scope="col">ID Plato</th>
                <th scope="col">Nombre</th>
                <th scope="col">Descripción</th>
                <th scope="col">Precio</th>
                <th scope="col">Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($plato = $platosResult->fetch_assoc()): ?>
                <tr>
                    <td><?= $plato['id_plato']; ?></td>
                    <td><?= $plato['nombre']; ?></td>
                    <td><?= $plato['descripcion']; ?></td>
                    <td><?= $plato['precio']; ?></td>
                    <td>
                        <a href="?delete_id=<?= $plato['id_plato']; ?>" class="delete-link" onclick="return confirm('¿Estás seguro de que deseas eliminar este plato?');">Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <script>
        // Función para agregar más campos de ingredientes
        function agregarIngrediente() {
            const div = document.createElement('div');
            div.innerHTML = `
                <label for="nuevo_ingrediente">Nuevo Ingrediente:</label>
                <input type="text" name="nuevos_ingredientes[]" placeholder="Nombre del Ingrediente">
                <input type="text" name="cantidades_nuevos[]" placeholder="Cantidad (e.g., 100g, 1 pieza)">
            `;
            document.querySelector('form').insertBefore(div, document.querySelector('input[type="submit"]'));
        }
    </script>
</body>
</html>