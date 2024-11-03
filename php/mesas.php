<?php
include 'db.php'; // Conexión a la base de datos

// Procesar el formulario de agregar mesa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    if (isset($_POST['numero_mesa']) && isset($_POST['estado'])) {
        $numero_mesa = $_POST['numero_mesa'];
        $estado = $_POST['estado'];

        // Verificar si ya existe una mesa con ese número
        $checkMesa = "SELECT * FROM Mesas WHERE numero_mesa = '$numero_mesa'";
        $result = $conn->query($checkMesa);

        if ($result->num_rows > 0) {
            echo "Error: El número de mesa ya existe.";
        } else {
            // Insertar la nueva mesa
            $insertMesa = "INSERT INTO Mesas (numero_mesa, estado) VALUES ('$numero_mesa', '$estado')";
            if ($conn->query($insertMesa) === TRUE) {
                // Redirigir a la misma página para evitar la duplicación de datos
                header("Location: mesas.php?success=1");
                exit();
            } else {
                echo "Error al agregar la mesa: " . $conn->error;
            }
        }
    }
}

// Procesar el formulario de actualización de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    if (isset($_POST['id_mesa']) && isset($_POST['estado'])) {
        $id_mesa = $_POST['id_mesa'];
        $estado = $_POST['estado'];

        // Actualizar el estado de la mesa
        $updateMesa = "UPDATE Mesas SET estado='$estado' WHERE id_mesa=$id_mesa";
        if ($conn->query($updateMesa) === TRUE) {
            // Redirigir a la misma página después de la actualización
            header("Location: mesas.php?updated=1");
            exit();
        } else {
            echo "Error al actualizar el estado de la mesa: " . $conn->error;
        }
    }
}

// Obtener mesas para mostrar en la administración
$mesasQuery = "SELECT * FROM Mesas";
$mesasResult = $conn->query($mesasQuery);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Mesas</title>
    <link rel="stylesheet" href="../css/mesas.css">
</head>
<body>

    <!-- Agregar Nueva Mesa -->
    <h2>Agregar Nueva Mesa</h2>
    <form method="POST" action="">
        <label for="numero_mesa">Número de Mesa:</label>
        <input type="number" name="numero_mesa" required>
        
        <label for="estado">Estado:</label>
        <select name="estado" required>
            <option value="libre">Libre</option>
            <option value="ocupada">Ocupada</option>
        </select>
        
        <input type="submit" name="agregar" value="Agregar Mesa">
    </form>

    <hr>

   <!-- Administrar Mesas Existentes -->
<h2>Administrar Mesas</h2>
<form method="POST" action="">
    <h3>Mesas Existentes</h3>
    <table>
        <tr>
            <th>ID Mesa</th>
            <th>Número de Mesa</th>
            <th>Estado</th>
            <th>Actualizar Estado</th>
        </tr>
        <?php while ($mesa = $mesasResult->fetch_assoc()): ?>
            <tr>
                <td><?= $mesa['id_mesa']; ?></td>
                <td><?= $mesa['numero_mesa']; ?></td>
                <td><?= $mesa['estado']; ?></td>
                <td>
                    <input type="radio" name="id_mesa" value="<?= $mesa['id_mesa']; ?>" required>
                    <select name="estado" required>
                        <option value="libre" <?= $mesa['estado'] == 'libre' ? 'selected' : ''; ?>>Libre</option>
                        <option value="ocupada" <?= $mesa['estado'] == 'ocupada' ? 'selected' : ''; ?>>Ocupada</option>
                    </select>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <input type="submit" name="actualizar" value="Actualizar Estado">
</form>


    <?php if (isset($_GET['success'])): ?>
        <p style="color: green;">Mesa agregada con éxito.</p>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <p style="color: green;">Estado de la mesa actualizado con éxito.</p>
    <?php endif; ?>

</body>
</html>
