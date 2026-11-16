<?php
// admin/reportes.php - Panel de Reportes y Estadísticas (solo Administrador)
require_once '../config/bd.php';
require_once '../config/sesion.php';
verificar_admin();

const ROL_ADOPTANTE = 3;

/* ---------- 1. Periodo seleccionado ---------- */
$periodos = [
    'mes'       => ['Último mes',      29,  'semana'],
    'trimestre' => ['Últimos 3 meses', 89,  'semana'],
    'anio'      => ['Último año',      364, 'mes'],
];
$periodo = $_GET['periodo'] ?? 'mes';
if (!isset($periodos[$periodo])) {
    $periodo = 'mes';
}
[$etiqueta_periodo, $dias_atras, $agrupar] = $periodos[$periodo];

$hoy    = new DateTime('today');
$inicio = (clone $hoy)->modify("-$dias_atras days");
$fin    = (clone $hoy)->modify('+1 day');          // límite exclusivo
$ini_sql = $inicio->format('Y-m-d H:i:s');
$fin_sql = $fin->format('Y-m-d H:i:s');

/* ---------- 2. Resumen general (COUNT) ---------- */
function contar($conexion, $sql, $tipos, ...$params) {
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($tipos, ...$params);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_row()[0];
}

$sql_adop = "SELECT COUNT(*)
             FROM solicitudes_adopcion s
             JOIN mascotas m ON m.id_mascota = s.id_mascota
             WHERE s.estado = 'Aprobada' AND m.especie = ?
               AND s.fecha_resolucion >= ? AND s.fecha_resolucion < ?";
$perros = contar($conexion, $sql_adop, 'sss', 'Perro', $ini_sql, $fin_sql);
$gatos  = contar($conexion, $sql_adop, 'sss', 'Gato',  $ini_sql, $fin_sql);

$total_adopciones = contar($conexion,
    "SELECT COUNT(*) FROM solicitudes_adopcion
     WHERE estado = 'Aprobada' AND fecha_resolucion >= ? AND fecha_resolucion < ?",
    'ss', $ini_sql, $fin_sql);

$rechazadas = contar($conexion,
    "SELECT COUNT(*) FROM solicitudes_adopcion
     WHERE estado = 'Rechazada' AND fecha_resolucion >= ? AND fecha_resolucion < ?",
    'ss', $ini_sql, $fin_sql);

$nuevos_usuarios = contar($conexion,
    "SELECT COUNT(*) FROM usuarios
     WHERE id_rol = ? AND fecha_registro >= ? AND fecha_registro < ?",
    'iss', ROL_ADOPTANTE, $ini_sql, $fin_sql);

/* ---------- 3. Adopciones por semana (o por mes en el reporte anual) ---------- */
$cubetas = [];   // etiqueta => cantidad
if ($agrupar === 'semana') {
    $num = (int)ceil((($dias_atras + 1)) / 7);
    for ($i = 0; $i < $num; $i++) {
        $a = (clone $inicio)->modify('+' . ($i * 7) . ' days');
        $b = min((clone $a)->modify('+6 days'), $hoy);
        $cubetas['Semana ' . ($i + 1) . ' (' . $a->format('d/m') . ' - ' . $b->format('d/m') . ')'] = 0;
    }
} else {
    $meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    $cursor = new DateTime($inicio->format('Y-m-01'));
    while ($cursor <= $hoy) {
        $cubetas[$meses[(int)$cursor->format('n') - 1] . ' ' . $cursor->format('Y')] = 0;
        $cursor->modify('+1 month');
    }
}
$claves = array_keys($cubetas);

$stmt = $conexion->prepare(
    "SELECT fecha_resolucion FROM solicitudes_adopcion
     WHERE estado = 'Aprobada' AND fecha_resolucion >= ? AND fecha_resolucion < ?"
);
$stmt->bind_param('ss', $ini_sql, $fin_sql);
$stmt->execute();
$res = $stmt->get_result();
while ($fila = $res->fetch_assoc()) {
    $f = new DateTime($fila['fecha_resolucion']);
    if ($agrupar === 'semana') {
        $idx = (int)floor($inicio->diff(new DateTime($f->format('Y-m-d')))->days / 7);
    } else {
        $idx = array_search($meses[(int)$f->format('n') - 1] . ' ' . $f->format('Y'), $claves, true);
    }
    if ($idx !== false && isset($claves[$idx])) {
        $cubetas[$claves[$idx]]++;
    }
}
$max_cubeta = max(1, max($cubetas));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - PetMatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            main { padding: 0 !important; }
            .card { box-shadow: none !important; border: 1px solid #ccc !important; }
            .progress, .progress-bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid">
<div class="row min-vh-100">

    <?php include '../includes/sidebar_admin.php'; ?>

    <main class="col-md-10 ms-sm-auto p-4">
        <h1 class="h3 fw-bold mb-0">Panel de reportes y estadísticas</h1>
        <p class="text-muted">
            Periodo: <strong><?= $etiqueta_periodo ?></strong>
            (<?= $inicio->format('d/m/Y') ?> al <?= $hoy->format('d/m/Y') ?>)
        </p>

        <!-- Filtro por periodo -->
        <form method="GET" class="row g-2 align-items-center mb-4 no-print">
            <div class="col-auto"><label class="col-form-label">Filtrar por periodo:</label></div>
            <div class="col-auto">
                <select name="periodo" class="form-select">
                    <?php foreach ($periodos as $clave => $datos): ?>
                        <option value="<?= $clave ?>" <?= $clave === $periodo ? 'selected' : '' ?>><?= $datos[0] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-dark">Aplicar</button></div>
        </form>

        <!-- Resumen general -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-bold">RESUMEN GENERAL</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span>Perros adoptados en el periodo</span><strong><?= $perros ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Gatos adoptados en el periodo</span><strong><?= $gatos ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between bg-light">
                    <strong>Total de adopciones</strong><strong><?= $total_adopciones ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Solicitudes rechazadas</span><strong><?= $rechazadas ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Nuevos usuarios registrados</span><strong><?= $nuevos_usuarios ?></strong>
                </li>
            </ul>
        </div>

        <!-- Adopciones por semana / mes -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white fw-bold">
                ADOPCIONES POR <?= $agrupar === 'semana' ? 'SEMANA' : 'MES' ?>
            </div>
            <div class="card-body">
                <?php foreach ($cubetas as $nombre => $cantidad): ?>
                    <div class="row align-items-center mb-2">
                        <div class="col-md-4 small"><?= htmlspecialchars($nombre) ?></div>
                        <div class="col-md-8">
                            <div class="progress" style="height: 24px;">
                                <div class="progress-bar bg-dark" role="progressbar"
                                     style="width: <?= round($cantidad / $max_cubeta * 100) ?>%;">
                                    <?= $cantidad > 0 ? $cantidad : '' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if ($total_adopciones === 0): ?>
                    <p class="text-muted small mb-0 mt-2">No hay adopciones aprobadas en este periodo.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center no-print">
            <button class="btn btn-outline-dark" onclick="window.print()">Imprimir Reporte (PDF)</button>
        </div>
    </main>
</div>
</div>
</body>
</html>