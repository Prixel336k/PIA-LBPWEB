<?php
// admin/solicitudes.php - Validar propuestas de adopción (Personal y Administrador)
require_once '../config/bd.php';
require_once '../config/sesion.php';
verificar_staff();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- 1. Procesar Aprobar / Rechazar (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_solicitud = (int)($_POST['id_solicitud'] ?? 0);
    $accion       = $_POST['accion'] ?? '';
    $notas        = trim($_POST['notas_internas'] ?? '');
    $id_revisor   = (int)$_SESSION['id_usuario'];

    if ($id_solicitud > 0 && in_array($accion, ['aprobar', 'rechazar'], true)) {
        $nuevo_estado_sol     = $accion === 'aprobar' ? 'Aprobada' : 'Rechazada';
        $nuevo_estado_mascota = $accion === 'aprobar' ? 'Adoptada' : 'Disponible';

        $conexion->begin_transaction();
        try {
            // Solo se resuelven solicitudes que sigan Pendientes
            $stmt = $conexion->prepare(
                "UPDATE solicitudes_adopcion
                 SET estado = ?, id_revisor = ?, fecha_resolucion = NOW(), notas_internas = ?
                 WHERE id_solicitud = ? AND estado = 'Pendiente'"
            );
            $stmt->bind_param('sisi', $nuevo_estado_sol, $id_revisor, $notas, $id_solicitud);
            $stmt->execute();

            if ($stmt->affected_rows === 1) {
                $stmt2 = $conexion->prepare(
                    "UPDATE mascotas m
                     JOIN solicitudes_adopcion s ON s.id_mascota = m.id_mascota
                     SET m.estado = ?
                     WHERE s.id_solicitud = ?"
                );
                $stmt2->bind_param('si', $nuevo_estado_mascota, $id_solicitud);
                $stmt2->execute();

                $conexion->commit();
                $_SESSION['flash'] = ['success', "La solicitud fue marcada como $nuevo_estado_sol."];
            } else {
                $conexion->rollback();
                $_SESSION['flash'] = ['warning', 'Esa solicitud ya había sido resuelta.'];
            }
        } catch (Throwable $e) {
            $conexion->rollback();
            $_SESSION['flash'] = ['danger', 'Ocurrió un error al guardar los cambios.'];
        }
    }
    // Redirige para evitar reenvío del formulario, conservando los filtros
    header('Location: solicitudes.php?' . http_build_query([
        'q'      => $_GET['q'] ?? '',
        'estado' => $_GET['estado'] ?? ''
    ]));
    exit;
}

/* ---------- 2. Filtros (GET) ---------- */
$q      = trim($_GET['q'] ?? '');
$estado = $_GET['estado'] ?? '';
$estados_validos = ['Pendiente', 'Aprobada', 'Rechazada'];

$sql = "SELECT s.id_solicitud, s.fecha_solicitud, s.motivo, s.estado, s.notas_internas,
               u.nombre_completo AS adoptante, u.correo,
               m.nombre AS mascota,
               p.ocupacion, p.edad, p.tipo_vivienda, p.tiene_otras_mascotas,
               p.horas_solo_mascota, p.ha_tenido_mascotas, p.motivo_adopcion
        FROM solicitudes_adopcion s
        JOIN usuarios u ON u.id_usuario = s.id_adoptante
        JOIN mascotas m ON m.id_mascota = s.id_mascota
        LEFT JOIN perfiles_adoptante p ON p.id_usuario = u.id_usuario
        WHERE 1 = 1";
$tipos  = '';
$params = [];

if ($q !== '') {
    $sql .= " AND (u.nombre_completo LIKE ? OR u.correo LIKE ?)";
    $tipos .= 'ss';
    $like = "%$q%";
    $params[] = $like;
    $params[] = $like;
}
if (in_array($estado, $estados_validos, true)) {
    $sql .= " AND s.estado = ?";
    $tipos .= 's';
    $params[] = $estado;
}
$sql .= " ORDER BY s.fecha_solicitud DESC";

$stmt = $conexion->prepare($sql);
if ($params) {
    $stmt->bind_param($tipos, ...$params);
}
$stmt->execute();
$solicitudes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function clase_estado($e) {
    return ['Pendiente' => 'warning text-dark', 'Aprobada' => 'success', 'Rechazada' => 'danger'][$e] ?? 'secondary';
}
function url_filtro($estado, $q) {
    return '?' . http_build_query(['q' => $q, 'estado' => $estado]);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes de adopción - PetMatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="d-flex min-vh-100">

    <!-- Menú lateral (si se ve raro, copia la estructura de admin/mascotas.php) -->
    <?php include '../includes/sidebar_admin.php'; ?>

    <main class="flex-grow-1 p-4">
        <h1 class="h3 fw-bold mb-0">Solicitudes de adopción</h1>
        <p class="text-muted">Revisa, aprueba o rechaza las solicitudes enviadas por los usuarios registrados.</p>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash[0] ?> alert-dismissible fade show">
                <?= htmlspecialchars($flash[1]) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="alert alert-secondary py-2">
            <strong>Vista Privada:</strong> esta página solo está disponible para empleados y administradores del albergue.
        </div>

        <!-- Buscador y filtros por estado -->
        <form method="GET" class="row g-2 align-items-center mb-3">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control" placeholder="Buscar por adoptante..."
                       value="<?= htmlspecialchars($q) ?>">
                <input type="hidden" name="estado" value="<?= htmlspecialchars($estado) ?>">
            </div>
            <div class="col-auto">
                <button class="btn btn-dark">Buscar</button>
            </div>
            <div class="col-md ms-md-auto text-md-end">
                <div class="btn-group">
                    <a href="<?= url_filtro('', $q) ?>" class="btn btn-outline-dark <?= $estado === '' ? 'active' : '' ?>">Todas</a>
                    <a href="<?= url_filtro('Pendiente', $q) ?>" class="btn btn-outline-dark <?= $estado === 'Pendiente' ? 'active' : '' ?>">Pendientes</a>
                    <a href="<?= url_filtro('Aprobada', $q) ?>" class="btn btn-outline-dark <?= $estado === 'Aprobada' ? 'active' : '' ?>">Aprobadas</a>
                    <a href="<?= url_filtro('Rechazada', $q) ?>" class="btn btn-outline-dark <?= $estado === 'Rechazada' ? 'active' : '' ?>">Rechazadas</a>
                </div>
            </div>
        </form>

        <!-- Tabla de solicitudes -->
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Adoptante (Usuario)</th>
                            <th>Mascota solicitada</th>
                            <th>Fecha de solicitud</th>
                            <th>Estado actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$solicitudes): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay solicitudes con esos filtros.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($solicitudes as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['adoptante']) ?>
                                <small class="text-muted">(<?= htmlspecialchars($s['correo']) ?>)</small></td>
                            <td><?= htmlspecialchars($s['mascota']) ?></td>
                            <td><?= date('d/m/Y', strtotime($s['fecha_solicitud'])) ?></td>
                            <td><span class="badge bg-<?= clase_estado($s['estado']) ?>"><?= $s['estado'] ?></span></td>
                            <td>
                                <button type="button" class="btn btn-outline-dark btn-sm btn-revisar"
                                        data-solicitud="<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>">
                                    Revisar propuesta
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white small text-muted">
                Mostrando <?= count($solicitudes) ?> registro(s)
            </div>
        </div>
    </main>
</div>

<!-- Modal: Validar Propuesta de Adopción -->
<div class="modal fade" id="modalRevisar" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" action="solicitudes.php?<?= htmlspecialchars(http_build_query(['q' => $q, 'estado' => $estado])) ?>"
              class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Validar Propuesta de Adopción</h5>
                    <small class="text-muted">Analiza la información del solicitante antes de tomar una decisión.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_solicitud" id="m_id">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Adoptante</label>
                        <input type="text" id="m_adoptante" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Mascota</label>
                        <input type="text" id="m_mascota" class="form-control" readonly>
                    </div>
                </div>

                <label class="form-label fw-bold">Motivo del adoptante</label>
                <textarea id="m_motivo" class="form-control mb-3" rows="3" readonly></textarea>

                <label class="form-label fw-bold">Perfil del adoptante</label>
                <ul class="list-group mb-3" id="m_perfil"></ul>

                <label class="form-label fw-bold">Notas internas (solo visibles por staff)</label>
                <textarea name="notas_internas" id="m_notas" class="form-control" rows="3"
                          placeholder="Ej. Se verificó el domicilio..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cerrar</button>
                <div id="m_botones">
                    <button type="submit" name="accion" value="rechazar" class="btn btn-danger">Rechazar Propuesta</button>
                    <button type="submit" name="accion" value="aprobar" class="btn btn-success">Aprobar Adopción</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('modalRevisar'));
const si_no = v => (v === null ? '—' : (parseInt(v) ? 'Sí' : 'No'));

document.querySelectorAll('.btn-revisar').forEach(btn => {
    btn.addEventListener('click', () => {
        const s = JSON.parse(btn.dataset.solicitud);

        document.getElementById('m_id').value        = s.id_solicitud;
        document.getElementById('m_adoptante').value = s.adoptante + ' (' + s.correo + ')';
        document.getElementById('m_mascota').value   = s.mascota;
        document.getElementById('m_motivo').value    = s.motivo || s.motivo_adopcion || '';
        document.getElementById('m_notas').value     = s.notas_internas || '';

        const lista = document.getElementById('m_perfil');
        if (s.ocupacion === null) {
            lista.innerHTML = '<li class="list-group-item text-muted">El adoptante no tiene perfil registrado.</li>';
        } else {
            lista.innerHTML = [
                ['Ocupación', s.ocupacion],
                ['Edad', s.edad + ' años'],
                ['Tipo de vivienda', s.tipo_vivienda],
                ['¿Tiene otras mascotas?', si_no(s.tiene_otras_mascotas)],
                ['Horas que pasará sola la mascota', s.horas_solo_mascota + ' h al día'],
                ['¿Ha tenido mascotas antes?', si_no(s.ha_tenido_mascotas)]
            ].map(([k, v]) =>
                `<li class="list-group-item d-flex justify-content-between"><span>${k}</span><strong>${v}</strong></li>`
            ).join('');
        }

        // Solo se puede decidir si sigue Pendiente
        const pendiente = s.estado === 'Pendiente';
        document.getElementById('m_botones').style.display = pendiente ? '' : 'none';
        document.getElementById('m_notas').readOnly = !pendiente;

        modal.show();
    });
});
</script>
</body>
</html>