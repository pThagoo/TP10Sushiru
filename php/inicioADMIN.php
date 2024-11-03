<?php

/*
-- Insertar usuario mesero
INSERT INTO usuarios (usuario, clave) 
VALUES ('mesero', 'mesero123');

-- Insertar usuario cocinero
INSERT INTO usuarios (usuario, clave) 
VALUES ('cocinero', 'cocinero123');

-- Insertar usuario admin
INSERT INTO usuarios (usuario, clave) 
VALUES ('admin', 'admin123');

*/ 
session_start();
include 'db.php'; // Conexión a la base de datos

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];

    // Depuración: Comprobar que los datos se envían correctamente
    echo "Usuario ingresado: " . htmlspecialchars($usuario) . "<br>";
    echo "Contraseña ingresada: " . htmlspecialchars($clave) . "<br>";

    // Consulta a la base de datos
    $sql = "SELECT * FROM usuarios WHERE usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();

        // Depuración: Comprobar que los datos de la base de datos se obtienen correctamente
        echo "Contraseña en la base de datos: " . $fila['clave'] . "<br>";

        // Comparar la contraseña ingresada con la de la base de datos
        if ($clave == $fila['clave']) {
            $_SESSION['usuario'] = $fila['usuario'];
            
            // Redirigir según el rol del usuario
            if ($fila['usuario'] == 'mesero') {
                header("Location: mesero.php");
            } elseif ($fila['usuario'] == 'cocinero') {
                header("Location: cocina.php");
            } elseif ($fila['usuario'] == 'admin') {
                header("Location: agregarPLATOS.php");
            } else {
                echo "Rol no asignado.";
            }
            exit();
        } else {
            echo "Contraseña incorrecta.";
        }
    } else {
        echo "Usuario no encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Restaurante de Sushi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <div class="login-container">
        <div class="login-box">
            <h2>Bienvenido</h2>
            <!-- Formulario Bootstrap-->
            <form  action="inicioADMIN.php" method="post">
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="usuario" aria-describedby="user" name="usuario" required>
                        <div id="user" class="form-text">Uso sólo para personal.</div>
                    </div>
                    <div class="mb-3">
                        <label for="clave" class="form-label">Password</label>
                        <input type="password" class="form-control" id="clave" name="clave" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                    </form>
        </div>
    </div>
</body>
</html>

