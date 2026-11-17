<?php
// Pantalla "Mis adopciones": el adoptante ve sus mascotas adoptadas, el estado de sus solicitudes y sus favoritos
require_once 'config/bd.php';
require_once 'config/sesion.php';
require_once 'includes/funciones_mascota.php';

// Solo pueden entrar usuarios con sesión iniciada
verificar_sesion();

// Esta pantalla solo aplica al rol Adoptante (3)
if ($_SESSION['id_rol'] != 3) {
    header("Location: index.php");
    exit;
}

$id_usuario = (int)$_SESSION['id_usuario'];

// Todas las solicitudes del adoptante, junto con los datos de la mascota
$sql = "SELECT s.id_solicitud, s.fecha_solicitud, s.estado, s.fecha_resolucion,
               m.id_mascota, m.nombre, m.especie, m.raza, m.edad_meses, m.foto, m.activo
        FROM solicitudes_adopcion s
        JOIN mascotas m ON m.id_mascota = s.id_mascota
        WHERE s.id_adoptante = ?
        ORDER BY s.fecha_solicitud DESC";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

// Separa las solicitudes aprobadas (mascotas adoptadas) y cuenta cada estado
$solicitudes = [];
$adoptadas   = [];
$conteo      = ['Pendiente' => 0, 'Aprobada' => 0, 'Rechazada' => 0];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $solicitudes[] = $fila;
    $conteo[$fila['estado']]++;
    if ($fila['estado'] === 'Aprobada') {
        $adoptadas[] = $fila;
    }
}

// Mascotas guardadas como favoritas
$sql_fav = "SELECT m.id_mascota, m.nombre, m.especie, m.raza, m.edad_meses, m.foto, m.estado
            FROM favoritos f
            JOIN mascotas m ON m.id_mascota = f.id_mascota
            WHERE f.id_usuario = ? AND m.activo = 1
            ORDER BY f.fecha_guardado DESC";
$stmt_fav = mysqli_prepare($conexion, $sql_fav);
mysqli_stmt_bind_param($stmt_fav, "i", $id_usuario);
mysqli_stmt_execute($stmt_fav);
$favoritos = mysqli_fetch_all(mysqli_stmt_get_result($stmt_fav), MYSQLI_ASSOC);

// Pestaña que se muestra al entrar (por defecto, la de adoptadas si ya tiene alguna)
$tabs_validas = ['adoptadas', 'solicitudes', 'favoritos'];
$tab = $_GET['tab'] ?? '';
if (!in_array($tab, $tabs_validas)) {
    $tab = count($adoptadas) > 0 ? 'adoptadas' : 'solicitudes';
}

// Clase de Bootstrap para la etiqueta de estado de la solicitud
function clase_estado_solicitud($estado) {
    if ($estado === 'Aprobada')  return 'bg-success';
    if ($estado === 'Rechazada') return 'bg-danger';
    return 'bg-warning text-dark';
}

// Convierte una fecha de la base de datos a dd/mm/aaaa
function fecha_corta($fecha) {
    return $fecha ? date('d/m/Y', strtotime($fecha)) : '—';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Adopciones - PetMatch</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <!-- Barra de navegación superior -->
    <?php include 'includes/navbar.php'; ?>

    <main class="container py-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold">MIS ADOPCIONES</h2>
            <p class="fst-italic text-muted mb-0">"Aquí puedes seguir tus solicitudes y ver a tus nuevos compañeros"</p>
        </div>

        <!-- Resumen -->
        <div class="row g-3 mb-4 text-center">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
                    <div class="fs-3 fw-bold"><?= count($solicitudes) ?></div>
                    <div class="small text-muted">Solicitudes enviadas</div>
                </div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
                    <div class="fs-3 fw-bold text-warning"><?= $conteo['Pendiente'] ?></div>
                    <div class="small text-muted">En revisión</div>
                </div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
                    <div class="fs-3 fw-bold text-success"><?= $conteo['Aprobada'] ?></div>
                    <div class="small text-muted">Adopciones aprobadas</div>
                </div></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
                    <div class="fs-3 fw-bold text-danger"><?= $conteo['Rechazada'] ?></div>
                    <div class="small text-muted">No aprobadas</div>
                </div></div>
            </div>
        </div>

        <!-- Pestañas -->
        <ul class="nav nav-tabs mb-4" id="pestanas" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $tab === 'adoptadas' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-adoptadas" type="button" role="tab">
                    Mis mascotas adoptadas <span class="badge bg-success ms-1"><?= count($adoptadas) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $tab === 'solicitudes' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-solicitudes" type="button" role="tab">
                    Mis solicitudes <span class="badge bg-secondary ms-1"><?= count($solicitudes) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $tab === 'favoritos' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#tab-favoritos" type="button" role="tab">
                    Mis favoritos <span class="badge bg-secondary ms-1"><?= count($favoritos) ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content">

            <!-- Mascotas adoptadas (solicitudes aprobadas) -->
            <div class="tab-pane fade <?= $tab === 'adoptadas' ? 'show active' : '' ?>" id="tab-adoptadas" role="tabpanel">
                <?php if (count($adoptadas) > 0): ?>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
                        <?php foreach ($adoptadas as $a): ?>
                            <div class="col">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="p-3 pb-0 position-relative">
                                        <?php mostrar_foto($a['foto'], $a['nombre'], 200, 'rounded'); ?>
                                        <span class="badge bg-success position-absolute top-0 end-0 m-4">¡Ya es tuya!</span>
                                    </div>
                                    <div class="card-body text-center">
                                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($a['nombre']) ?></h5>
                                        <p class="text-muted small mb-1">
                                            <?= htmlspecialchars($a['especie'] . ' · ' . $a['raza']) ?> · <?= edad_texto($a['edad_meses']) ?>
                                        </p>
                                        <p class="small mb-0">Adoptada el <strong><?= fecha_corta($a['fecha_resolucion']) ?></strong></p>
                                    </div>
                                    <?php if ($a['activo']): ?>
                                        <div class="card-footer bg-white border-0 pb-3">
                                            <a href="mascota.php?id=<?= (int)$a['id_mascota'] ?>" class="btn btn-outline-dark w-100">Ver perfil</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <p class="text-muted mb-3">
                                Aún no tienes mascotas adoptadas. Cuando el personal del albergue apruebe una de tus solicitudes, aparecerá aquí.
                            </p>
                            <a href="adoptar.php" class="btn btn-dark">Ver mascotas disponibles</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Todas las solicitudes con su estado -->
            <div class="tab-pane fade <?= $tab === 'solicitudes' ? 'show active' : '' ?>" id="tab-solicitudes" role="tabpanel">
                <?php if (count($solicitudes) > 0): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Mascota</th>
                                        <th>Fecha de solicitud</th>
                                        <th>Estado</th>
                                        <th>Fecha de respuesta</th>
                                        <th>Detalle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($solicitudes as $s): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div style="width: 64px;"><?php mostrar_foto($s['foto'], $s['nombre'], 48, 'rounded'); ?></div>
                                                    <div>
                                                        <?php if ($s['activo']): ?>
                                                            <a href="mascota.php?id=<?= (int)$s['id_mascota'] ?>" class="fw-semibold text-decoration-none text-dark">
                                                                <?= htmlspecialchars($s['nombre']) ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="fw-semibold"><?= htmlspecialchars($s['nombre']) ?></span>
                                                        <?php endif; ?>
                                                        <div class="text-muted small"><?= htmlspecialchars($s['especie'] . ' · ' . $s['raza']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= fecha_corta($s['fecha_solicitud']) ?></td>
                                            <td><span class="badge <?= clase_estado_solicitud($s['estado']) ?>"><?= htmlspecialchars($s['estado']) ?></span></td>
                                            <td><?= fecha_corta($s['fecha_resolucion']) ?></td>
                                            <td class="small text-muted">
                                                <?php if ($s['estado'] === 'Pendiente'): ?>
                                                    El personal del albergue está revisando tu solicitud.
                                                <?php elseif ($s['estado'] === 'Aprobada'): ?>
                                                    ¡Felicidades! Ya es tuya, la encuentras en "Mis mascotas adoptadas".
                                                <?php else: ?>
                                                    Esta solicitud no fue aprobada. Puedes buscar otra mascota.
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <p class="text-muted mb-3">Todavía no has enviado ninguna solicitud de adopción.</p>
                            <a href="adoptar.php" class="btn btn-dark">Buscar mascotas</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Favoritos -->
            <div class="tab-pane fade <?= $tab === 'favoritos' ? 'show active' : '' ?>" id="tab-favoritos" role="tabpanel">
                <?php if (count($favoritos) > 0): ?>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
                        <?php foreach ($favoritos as $f): ?>
                            <div class="col">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="p-3 pb-0">
                                        <?php mostrar_foto($f['foto'], $f['nombre'], 200, 'rounded'); ?>
                                    </div>
                                    <div class="card-body text-center">
                                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($f['nombre']) ?></h5>
                                        <p class="text-muted small mb-2">
                                            <?= htmlspecialchars($f['especie'] . ' · ' . $f['raza']) ?> · <?= edad_texto($f['edad_meses']) ?>
                                        </p>
                                        <span class="badge <?= clase_badge_estado($f['estado']) ?>"><?= htmlspecialchars($f['estado']) ?></span>
                                    </div>
                                    <div class="card-footer bg-white border-0 pb-3">
                                        <a href="mascota.php?id=<?= (int)$f['id_mascota'] ?>" class="btn btn-outline-dark w-100">Ver perfil</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <p class="text-muted mb-3">Aún no tienes mascotas favoritas. Guarda las que más te gusten desde su perfil.</p>
                            <a href="adoptar.php" class="btn btn-dark">Ver mascotas disponibles</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
