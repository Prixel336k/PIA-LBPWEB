<?php
// admin/personal.php - Personal registrado (solo Administrador)
require_once '../config/bd.php';
require_once '../config/sesion.php';
verificar_admin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const ROL_PERSONAL = 2;

/* ---------- 1. Procesar acciones (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id     = (int)($_POST['id_usuario'] ?? 0);
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $pass   = $_POST['contrasena'] ?? '';

    try {
        if ($accion === 'agregar') {
            if ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
                $_SESSION['flash'] = ['danger', 'Revisa los datos: nombre, correo válido y contraseña de al menos 6 caracteres.'];
            } else {
                $hash   = password_hash($pass, PASSWORD_DEFAULT);
                $rol    = ROL_PERSONAL;
                $creado = (int)$_SESSION['id_usuario'];
                $stmt = $conexion->prepare(
                    "INSERT INTO usuarios (id_rol, nombre_completo, correo, contrasena_hash, estado, id_creado_por)
                     VALUES (?, ?, ?, ?, 'Activo', ?)"
                );
                $stmt->bind_param('isssi', $rol, $nombre, $correo, $hash, $creado);
                $stmt->execute();
                $_SESSION['flash'] = ['success', 'Cuenta de personal creada correctamente.'];
            }

        } elseif ($accion === 'editar' && $id > 0) {
            if ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL) || ($pass !== '' && strlen($pass) < 6)) {
                $_SESSION['flash'] = ['danger', 'Revisa los datos: nombre, correo válido y contraseña de al menos 6 caracteres (o déjala vacía).'];
            } else {
                $rol = ROL_PERSONAL;
                if ($pass !== '') {
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $stmt = $conexion->prepare(
                        "UPDATE usuarios SET nombre_completo = ?, correo = ?, contrasena_hash = ?
                         WHERE id_usuario = ? AND id_rol = ?"
                    );
                    $stmt->bind_param('sssii', $nombre, $correo, $hash, $id, $rol);
                } else {
                    $stmt = $conexion->prepare(
                        "UPDATE usuarios SET nombre_completo = ?, correo = ?
                         WHERE id_usuario = ? AND id_rol = ?"
                    );
                    $stmt->bind_param('ssii', $nombre, $correo, $id, $rol);
                }
                $stmt->execute();
                $_SESSION['flash'] = ['success', 'Datos actualizados correctamente.'];
            }

        } elseif (($accion === 'baja' || $accion === 'activar') && $id > 0) {
            $nuevo = $accion === 'baja' ? 'Inactivo' : 'Activo';
            $rol   = ROL_PERSONAL;
            $stmt = $conexion->prepare("UPDATE usuarios SET estado = ? WHERE id_usuario = ? AND id_rol = ?");
            $stmt->bind_param('sii', $nuevo, $id, $rol);
            $stmt->execute();
            $_SESSION['flash'] = ['success', $accion === 'baja' ? 'La cuenta fue dada de baja.' : 'La cuenta fue activada.'];
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {
            $_SESSION['flash'] = ['danger', 'Ese correo ya está registrado en otra cuenta.'];
        } else {
            $_SESSION['flash'] = ['danger', 'Ocurrió un error al guardar los cambios.'];
        }
    }

    header('Location: personal.php?' . http_build_query([
        'q'      => $_GET['q'] ?? '',
        'estado' => $_GET['estado'] ?? ''
    ]));
    exit;
}

/* ---------- 2. Filtros (GET) ---------- */
$q      = trim($_GET['q'] ?? '');
$estado = $_GET['estado'] ?? '';

$sql    = "SELECT id_usuario, nombre_completo, correo, fecha_registro, estado
           FROM usuarios WHERE id_rol = ?";
$tipos  = 'i';
$rol    = ROL_PERSONAL;
$params = [$rol];

if ($q !== '') {
    $sql .= " AND nombre_completo LIKE ?";
    $tipos .= 's';
    $params[] = "%$q%";
}
if (in_array($estado, ['Activo', 'Inactivo'], true)) {
    $sql .= " AND estado = ?";
    $tipos .= 's';
    $params[] = $estado;
}
$sql .= " ORDER BY fecha_registro DESC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param($tipos, ...$params);
$stmt->execute();
$personal = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function url_filtro($estado, $q) {
    return '?' . http_build_query(['q' => $q, 'estado' => $estado]);
}
$action_url = 'personal.php?' . http_build_query(['q' => $q, 'estado' => $estado]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal registrado - PetMatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="d-flex min-vh-100">

    <!-- Menú lateral (si se ve raro, copia la estructura de admin/mascotas.php) -->
    <?php include '../includes/sidebar_admin.php'; ?>

    <main class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h1 class="h3 fw-bold mb-0">Personal registrado</h1>
                <p class="text-muted">Alta, edición y baja de cuentas del personal que administra los perfiles de animales.</p>
            </div>
            <button class="btn btn-dark" id="btnAgregar">+ Agregar personal</button>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash[0] ?> alert-dismissible fade show">
                <?= htmlspecialchars($flash[1]) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="alert alert-secondary py-2">
            Solo el Administrador puede crear, editar o dar de baja cuentas de personal.
        </div>

        <!-- Buscador y filtros -->
        <form method="GET" class="row g-2 align-items-center mb-3">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control" placeholder="Buscar por nombre..."
                       value="<?= htmlspecialchars($q) ?>">
                <input type="hidden" name="estado" value="<?= htmlspecialchars($estado) ?>">
            </div>
            <div class="col-auto">
                <button class="btn btn-dark">Buscar</button>
            </div>
            <div class="col-md ms-md-auto text-md-end">
                <div class="btn-group">
                    <a href="<?= url_filtro('', $q) ?>" class="btn btn-outline-dark <?= $estado === '' ? 'active' : '' ?>">Todos</a>
                    <a href="<?= url_filtro('Activo', $q) ?>" class="btn btn-outline-dark <?= $estado === 'Activo' ? 'active' : '' ?>">Activos</a>
                    <a href="<?= url_filtro('Inactivo', $q) ?>" class="btn btn-outline-dark <?= $estado === 'Inactivo' ? 'active' : '' ?>">Inactivos</a>
                </div>
            </div>
        </form>

        <!-- Tabla -->
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Alta</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$personal): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay personal con esos filtros.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($personal as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nombre_completo']) ?></td>
                            <td><?= htmlspecialchars($p['correo']) ?></td>
                            <td><?= date('d/m/Y', strtotime($p['fecha_registro'])) ?></td>
                            <td>
                                <span class="badge bg-<?= $p['estado'] === 'Activo' ? 'success' : 'secondary' ?>">
                                    <?= $p['estado'] ?>
                                </span>
                            </td>
                            <td class="d-flex gap-1">
                                <button type="button" class="btn btn-outline-dark btn-sm btn-editar"
                                        data-persona="<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>">
                                    Editar
                                </button>
                                <form method="POST" action="<?= htmlspecialchars($action_url) ?>" class="d-inline"
                                      onsubmit="return confirm('¿Seguro que deseas continuar?');">
                                    <input type="hidden" name="id_usuario" value="<?= $p['id_usuario'] ?>">
                                    <?php if ($p['estado'] === 'Activo'): ?>
                                        <button name="accion" value="baja" class="btn btn-outline-danger btn-sm">Dar de baja</button>
                                    <?php else: ?>
                                        <button name="accion" value="activar" class="btn btn-outline-success btn-sm">Activar</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white small text-muted">
                Mostrando <?= count($personal) ?> registro(s)
            </div>
        </div>
    </main>
</div>

<!-- Modal: Agregar / Editar personal -->
<div class="modal fade" id="modalPersonal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= htmlspecialchars($action_url) ?>" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mp_titulo">Agregar personal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="accion" id="mp_accion" value="agregar">
                <input type="hidden" name="id_usuario" id="mp_id">

                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre completo</label>
                    <input type="text" name="nombre_completo" id="mp_nombre" class="form-control" maxlength="100" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Correo electrónico</label>
                    <input type="email" name="correo" id="mp_correo" class="form-control" maxlength="120" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Contraseña</label>
                    <input type="password" name="contrasena" id="mp_pass" class="form-control" minlength="6" autocomplete="new-password">
                    <div class="form-text" id="mp_ayuda_pass"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-dark">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const modal = new bootstrap.Modal(document.getElementById('modalPersonal'));
const $ = id => document.getElementById(id);

$('btnAgregar').addEventListener('click', () => {
    $('mp_titulo').textContent = 'Agregar personal';
    $('mp_accion').value = 'agregar';
    $('mp_id').value = '';
    $('mp_nombre').value = '';
    $('mp_correo').value = '';
    $('mp_pass').value = '';
    $('mp_pass').required = true;
    $('mp_ayuda_pass').textContent = 'Mínimo 6 caracteres.';
    modal.show();
});

document.querySelectorAll('.btn-editar').forEach(btn => {
    btn.addEventListener('click', () => {
        const p = JSON.parse(btn.dataset.persona);
        $('mp_titulo').textContent = 'Editar personal';
        $('mp_accion').value = 'editar';
        $('mp_id').value = p.id_usuario;
        $('mp_nombre').value = p.nombre_completo;
        $('mp_correo').value = p.correo;
        $('mp_pass').value = '';
        $('mp_pass').required = false;
        $('mp_ayuda_pass').textContent = 'Déjala vacía si no quieres cambiar la contraseña.';
        modal.show();
    });
});
</script>
</body>
</html>