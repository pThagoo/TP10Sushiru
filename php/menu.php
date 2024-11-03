<?php
include 'db.php'; // Conexión a la base de datos

// Consulta para obtener todos los platos
$platosResult = $conn->query("SELECT id_plato, nombre, descripcion, precio, imagen FROM Platos");

if (!$platosResult) {
    echo "Error al obtener los platos: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú - Sushiru</title>
    <link rel="stylesheet" href="../css/menu.css">
</head>
<body>

<!-- Header -->
<header>
    <div class="navbar">
        <div class="logo">
            <a href="index.html">SUSHIRU</a>
        </div>
        <ul class="nav-links">
            <li><a href="../html/index.html">Inicio</a></li>
            <li><a href="../php/menu.php">Menú</a></li>
            <li><a href="../html/nosotros.html">Nosotros</a></li>
            <li><a href="../php/loginusuario.php">Iniciar Sesion</a></li>
        </ul>
    </div>
</header>

<!-- Sección de Menú -->
<section id="menu" class="menu">
    <h2>Menú de Sushiru</h2>
    <div class="menu-grid">
        <?php if ($platosResult->num_rows > 0): ?>
            <?php while ($plato = $platosResult->fetch_assoc()): ?>
                <div class="menu-item">
                    <img src="<?= $plato['imagen']; ?>" alt="<?= $plato['nombre']; ?>" style="width:100px;">
                    <h3><?= $plato['nombre']; ?></h3>
                    <p><?= $plato['descripcion']; ?></p>
                    <p>Precio: $<?= $plato['precio']; ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No hay platos disponibles en este momento.</p>
        <?php endif; ?>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="footer-container">
        <div class="footer-logo">
            <a href="#">Sushiru</a>
        </div>
        <ul class="footer-links">
            <li><a href="#">Política de Privacidad</a></li>
            <li><a href="#">Términos de Uso</a></li>
        </ul>
        <div class="footer-info">
            <p>&copy; 2024 Sushiru. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>

</body>
</html>
