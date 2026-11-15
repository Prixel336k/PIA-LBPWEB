<?php
// Funciones de apoyo para mostrar las mascotas (se usan en adoptar.php y mascota.php)

// Convierte la edad en meses a un texto legible (años o meses)
function edad_texto($meses) {
    $meses = (int)$meses;
    if ($meses >= 12) {
        $anios = floor($meses / 12);
        return $anios . ($anios == 1 ? ' año' : ' años');
    }
    return $meses . ($meses == 1 ? ' mes' : ' meses');
}

// Regresa la clase de Bootstrap para la etiqueta de estado de la mascota
function clase_badge_estado($estado) {
    if ($estado === 'En proceso') return 'bg-warning text-dark';
    if ($estado === 'Adoptada')   return 'bg-secondary';
    return 'bg-success';
}

// Muestra la foto de la mascota; si el archivo no existe, muestra un recuadro "Foto Mascota"
function mostrar_foto($ruta, $nombre, $alto, $clase = '') {
    $archivo = __DIR__ . '/../' . $ruta;
    if ($ruta !== '' && is_file($archivo)) {
        echo '<img src="' . htmlspecialchars($ruta) . '" alt="Foto de ' . htmlspecialchars($nombre) . '" '
           . 'class="w-100 ' . $clase . '" style="height: ' . (int)$alto . 'px; object-fit: cover;">';
    } else {
        echo '<div class="w-100 d-flex align-items-center justify-content-center bg-white text-muted small ' . $clase . '" '
           . 'style="height: ' . (int)$alto . 'px; border: 2px dashed #adb5bd;">Foto Mascota</div>';
    }
}
?>
