<?php
$en_admin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$ruta_base = $en_admin ? '../' : '';
?>
<!-- Footer -->
<footer class="bg-white border-top py-3 mt-auto">
    <div class="container d-flex flex-wrap justify-content-between align-items-center">
        <span class="text-muted small">
            <strong>PetMatch</strong> &copy; <?= date('Y') ?> - Refugio de Mascotas
        </span>
        <div class="d-flex gap-2">
            <a href="<?= $ruta_base ?>nosotros.php" class="btn btn-outline-secondary btn-sm">Nosotros</a>
            <a href="<?= $ruta_base ?>contacto.php" class="btn btn-outline-secondary btn-sm">Contacto</a>
        </div>
    </div>
</footer>
