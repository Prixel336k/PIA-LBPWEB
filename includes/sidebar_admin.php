<?php
// Menú lateral para las pantallas del panel administrativo
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>
<aside class="col-md-3 col-lg-2 bg-dark text-white min-vh-100 p-3">
    <h5 class="fw-bold mb-4 pb-2 border-bottom border-secondary">Panel administrativo</h5>

    <p class="text-uppercase text-secondary small fw-bold mb-2">Pantallas CRUD</p>
    <ul class="nav nav-pills flex-column mb-4 gap-1">
        <li class="nav-item">
            <a href="mascotas.php" class="nav-link text-white <?= $pagina_actual === 'mascotas.php' ? 'active bg-secondary' : '' ?>">
                Mascotas
            </a>
        </li>
        <li class="nav-item">
            <a href="solicitudes.php" class="nav-link text-white <?= $pagina_actual === 'solicitudes.php' ? 'active bg-secondary' : '' ?>">
                Solicitudes de adopción
            </a>
        </li>
        <?php if (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1): ?>
            <li class="nav-item">
                <a href="personal.php" class="nav-link text-white <?= $pagina_actual === 'personal.php' ? 'active bg-secondary' : '' ?>">
                    Personal registrado
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <?php if (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1): ?>
        <p class="text-uppercase text-secondary small fw-bold mb-2">Otros</p>
        <ul class="nav nav-pills flex-column mb-4 gap-1">
            <li class="nav-item">
                <a href="reportes.php" class="nav-link text-white <?= $pagina_actual === 'reportes.php' ? 'active bg-secondary' : '' ?>">
                    Reportes
                </a>
            </li>
        </ul>
    <?php endif; ?>

    <hr class="border-secondary">
    <div class="d-grid gap-2">
        <a href="../index.php" class="btn btn-outline-light btn-sm">Ir al sitio público</a>
        <a href="../logout.php" class="btn btn-danger btn-sm">Cerrar Sesión</a>
    </div>
</aside>
