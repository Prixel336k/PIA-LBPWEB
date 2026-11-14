<?php
// Inicia la sesión si todavía no está iniciada para saber si el usuario ya entró
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detecta si estamos dentro de la carpeta /admin/ para ajustar las rutas de los enlaces
$en_admin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$ruta_base = $en_admin ? '../' : '';
?>
<!-- Barra de navegación superior -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-3">
    <div class="container">
        <!-- Logo / Nombre del sitio -->
        <a class="navbar-brand fw-bold fs-4 text-primary" href="<?= $ruta_base ?>index.php">
            PetMatch
        </a>

        <!-- Botón de menú para celulares -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <!-- Enlaces centrales (Nosotros, Contacto, Animales) -->
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-2">
                <li class="nav-item">
                    <a class="nav-link px-3 border rounded" href="<?= $ruta_base ?>nosotros.php">Nosotros</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 border rounded" href="<?= $ruta_base ?>contacto.php">Contacto</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 border rounded" href="<?= $ruta_base ?>adoptar.php">Animales</a>
                </li>
            </ul>

            <!-- Botones de la derecha (Cambian según si el usuario inició sesión) -->
            <div class="d-flex gap-2 align-items-center">
                <?php if (isset($_SESSION['id_usuario'])): ?>
                    <span class="text-muted small me-2">
                        Hola, <strong><?= htmlspecialchars($_SESSION['nombre_completo']) ?></strong>
                    </span>

                    <?php if ($_SESSION['id_rol'] == 1 || $_SESSION['id_rol'] == 2): ?>
                        <!-- Si es administrador (1) o personal del albergue (2), ve el boton al Panel -->
                        <a href="<?= $ruta_base ?>admin/mascotas.php" class="btn btn-outline-dark btn-sm">
                            Panel Administrativo
                        </a>
                    <?php else: ?>
                        <!-- Si es adoptante (3), ve el botón de Mi perfil -->
                        <a href="<?= $ruta_base ?>perfil.php" class="btn btn-outline-secondary btn-sm">
                            Mi perfil
                        </a>
                    <?php endif; ?>

                    <a href="<?= $ruta_base ?>logout.php" class="btn btn-outline-danger btn-sm">
                        Cerrar Sesión
                    </a>
                <?php else: ?>
                    <!-- Si es visitante sin sesión ve Crear Cuenta y Login -->
                    <a href="<?= $ruta_base ?>login.php?tab=registro" class="btn btn-outline-primary btn-sm px-3">
                        Crear Cuenta
                    </a>
                    <a href="<?= $ruta_base ?>login.php" class="btn btn-primary btn-sm px-3">
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
