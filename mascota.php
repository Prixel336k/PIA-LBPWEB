<?php
// Perfil individual de una mascota: favoritos y solicitud de adopción
require_once 'config/bd.php';
require_once 'config/sesion.php';
require_once 'includes/funciones_mascota.php';

// Recibe el id de la mascota por la URL (mascota.php?id=X)
$id_mascota = (int)($_GET['id'] ?? 0);

// Busca la mascota (solo si está activa, es decir, no eliminada)
$sql  = "SELECT id_mascota, nombre, especie, raza, sexo, tamano, edad_meses, descripcion, foto, estado, fecha_publicacion
         FROM mascotas WHERE id_mascota = ? AND activo = 1 LIMIT 1";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_mascota);
mysqli_stmt_execute($stmt);
$mascota = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$mascota) {
    http_response_code(404);
}

// Datos de la sesión: solo el rol Adoptante (3) puede guardar favoritos y solicitar
$con_sesion   = isset($_SESSION['id_usuario']);
$es_adoptante = $con_sesion && $_SESSION['id_rol'] == 3;
$id_usuario   = $con_sesion ? (int)$_SESSION['id_usuario'] : 0;

// Mensajes que se muestran después de guardar favorito o enviar la solicitud
$mensajes = [
    'fav_agregado'  => ['success', 'La mascota se guardó en tus favoritos.'],
    'fav_quitado'   => ['secondary', 'La mascota se quitó de tus favoritos.'],
    'solicitud_ok'  => ['success', 'Tu solicitud de adopción fue enviada. El personal del albergue la revisará pronto.'],
    'sin_perfil'    => ['warning', 'Antes de solicitar una adopción debes completar tu perfil de adoptante.'],
    'no_disponible' => ['warning', 'Esta mascota ya no está disponible para nuevas solicitudes.'],
    'ya_solicitada' => ['warning', 'Ya tienes una solicitud pendiente para esta mascota.'],
    'motivo_vacio'  => ['danger', 'Escribe el motivo de tu solicitud para poder enviarla.'],
    'error'         => ['danger', 'Ocurrió un error al procesar tu petición. Inténtalo de nuevo.'],
];
$clave_msg = $_GET['msg'] ?? '';
$aviso     = $mensajes[$clave_msg] ?? null;

// Revisa si el adoptante ya tiene perfil y qué motivo escribió en él
$perfil = null;
if ($es_adoptante) {
    $stmt_p = mysqli_prepare($conexion, "SELECT motivo_adopcion FROM perfiles_adoptante WHERE id_usuario = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt_p, "i", $id_usuario);
    mysqli_stmt_execute($stmt_p);
    $perfil = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_p));
}

// Procesa los formularios (favorito / solicitar) y regresa a la misma página con un mensaje
if ($mascota && $es_adoptante && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion  = $_POST['accion'] ?? '';
    $destino = "mascota.php?id=" . $id_mascota . "&msg=";

    try {
        // Guardar o quitar de favoritos
        if ($accion === 'favorito') {
            $stmt_f = mysqli_prepare($conexion, "SELECT 1 FROM favoritos WHERE id_usuario = ? AND id_mascota = ?");
            mysqli_stmt_bind_param($stmt_f, "ii", $id_usuario, $id_mascota);
            mysqli_stmt_execute($stmt_f);
            mysqli_stmt_store_result($stmt_f);

            if (mysqli_stmt_num_rows($stmt_f) > 0) {
                $stmt_d = mysqli_prepare($conexion, "DELETE FROM favoritos WHERE id_usuario = ? AND id_mascota = ?");
                mysqli_stmt_bind_param($stmt_d, "ii", $id_usuario, $id_mascota);
                mysqli_stmt_execute($stmt_d);
                header("Location: " . $destino . "fav_quitado");
            } else {
                $stmt_i = mysqli_prepare($conexion, "INSERT INTO favoritos (id_usuario, id_mascota) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt_i, "ii", $id_usuario, $id_mascota);
                mysqli_stmt_execute($stmt_i);
                header("Location: " . $destino . "fav_agregado");
            }
            exit;
        }

        // Enviar solicitud de adopción
        if ($accion === 'solicitar') {
            $motivo = trim($_POST['motivo'] ?? '');

            // 1) El adoptante debe haber completado su perfil
            if (!$perfil) {
                header("Location: " . $destino . "sin_perfil");
                exit;
            }
            // 2) La mascota debe seguir disponible
            if ($mascota['estado'] !== 'Disponible') {
                header("Location: " . $destino . "no_disponible");
                exit;
            }
            // 3) No se permiten solicitudes pendientes repetidas
            $stmt_s = mysqli_prepare($conexion, "SELECT 1 FROM solicitudes_adopcion
                                                 WHERE id_adoptante = ? AND id_mascota = ? AND estado = 'Pendiente'");
            mysqli_stmt_bind_param($stmt_s, "ii", $id_usuario, $id_mascota);
            mysqli_stmt_execute($stmt_s);
            mysqli_stmt_store_result($stmt_s);
            if (mysqli_stmt_num_rows($stmt_s) > 0) {
                header("Location: " . $destino . "ya_solicitada");
                exit;
            }
            // 4) Debe escribir el motivo
            if ($motivo === '') {
                header("Location: " . $destino . "motivo_vacio");
                exit;
            }

            // INSERT de la solicitud con estado 'Pendiente'
            $motivo = mb_substr($motivo, 0, 1000);
            $stmt_ins = mysqli_prepare($conexion, "INSERT INTO solicitudes_adopcion (id_adoptante, id_mascota, motivo, estado)
                                                   VALUES (?, ?, ?, 'Pendiente')");
            mysqli_stmt_bind_param($stmt_ins, "iis", $id_usuario, $id_mascota, $motivo);
            mysqli_stmt_execute($stmt_ins);
            header("Location: " . $destino . "solicitud_ok");
            exit;
        }
    } catch (mysqli_sql_exception $e) {
        header("Location: " . $destino . "error");
        exit;
    }
}

// Estado actual del adoptante respecto a esta mascota (después de procesar los formularios)
$es_favorita         = false;
$solicitud_pendiente = false;
$adoptada_por_mi     = false;
if ($mascota && $es_adoptante) {
    $stmt_f = mysqli_prepare($conexion, "SELECT 1 FROM favoritos WHERE id_usuario = ? AND id_mascota = ?");
    mysqli_stmt_bind_param($stmt_f, "ii", $id_usuario, $id_mascota);
    mysqli_stmt_execute($stmt_f);
    mysqli_stmt_store_result($stmt_f);
    $es_favorita = mysqli_stmt_num_rows($stmt_f) > 0;

    $stmt_s = mysqli_prepare($conexion, "SELECT 1 FROM solicitudes_adopcion
                                         WHERE id_adoptante = ? AND id_mascota = ? AND estado = 'Pendiente'");
    mysqli_stmt_bind_param($stmt_s, "ii", $id_usuario, $id_mascota);
    mysqli_stmt_execute($stmt_s);
    mysqli_stmt_store_result($stmt_s);
    $solicitud_pendiente = mysqli_stmt_num_rows($stmt_s) > 0;

    // ¿El personal ya aprobó la adopción de esta mascota para este adoptante?
    $stmt_a = mysqli_prepare($conexion, "SELECT 1 FROM solicitudes_adopcion
                                         WHERE id_adoptante = ? AND id_mascota = ? AND estado = 'Aprobada'");
    mysqli_stmt_bind_param($stmt_a, "ii", $id_usuario, $id_mascota);
    mysqli_stmt_execute($stmt_a);
    mysqli_stmt_store_result($stmt_a);
    $adoptada_por_mi = mysqli_stmt_num_rows($stmt_a) > 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $mascota ? htmlspecialchars($mascota['nombre']) . ' - PetMatch' : 'Mascota no encontrada - PetMatch' ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <!-- Barra de navegación superior -->
    <?php include 'includes/navbar.php'; ?>

    <main class="container py-5">
        <a href="adoptar.php" class="btn btn-outline-secondary btn-sm mb-3">&larr; Volver al catálogo</a>

        <?php if ($aviso): ?>
            <div class="alert alert-<?= $aviso[0] ?> py-2 small">
                <?= htmlspecialchars($aviso[1]) ?>
                <?php if ($clave_msg === 'solicitud_ok'): ?>
                    <a href="mis_adopciones.php?tab=solicitudes" class="alert-link">Ver mis solicitudes</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$mascota): ?>
            <!-- Mascota inexistente o eliminada -->
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <h4 class="fw-bold">Mascota no encontrada</h4>
                    <p class="text-muted">Es posible que el perfil ya no esté disponible.</p>
                    <a href="adoptar.php" class="btn btn-dark">Ver mascotas disponibles</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <!-- Foto de la mascota -->
                <div class="col-md-5">
                    <div class="card border-0 shadow-sm p-3">
                        <?php mostrar_foto($mascota['foto'], $mascota['nombre'], 380, 'rounded'); ?>
                    </div>
                </div>

                <!-- Información y acciones -->
                <div class="col-md-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h2 class="fw-bold mb-0"><?= htmlspecialchars($mascota['nombre']) ?></h2>
                                <span class="badge <?= clase_badge_estado($mascota['estado']) ?> fs-6">
                                    <?= htmlspecialchars($mascota['estado']) ?>
                                </span>
                            </div>

                            <div class="row g-2 small mb-3">
                                <div class="col-6"><span class="text-muted">Especie:</span> <strong><?= htmlspecialchars($mascota['especie']) ?></strong></div>
                                <div class="col-6"><span class="text-muted">Raza:</span> <strong><?= htmlspecialchars($mascota['raza']) ?></strong></div>
                                <div class="col-6"><span class="text-muted">Sexo:</span> <strong><?= htmlspecialchars($mascota['sexo']) ?></strong></div>
                                <div class="col-6"><span class="text-muted">Tamaño:</span> <strong><?= htmlspecialchars($mascota['tamano']) ?></strong></div>
                                <div class="col-6"><span class="text-muted">Edad:</span> <strong><?= edad_texto($mascota['edad_meses']) ?></strong></div>
                                <div class="col-6"><span class="text-muted">Publicada:</span> <strong><?= date('d/m/Y', strtotime($mascota['fecha_publicacion'])) ?></strong></div>
                            </div>

                            <h6 class="fw-bold border-bottom pb-2">Descripción</h6>
                            <p class="text-secondary">
                                <?= $mascota['descripcion'] ? nl2br(htmlspecialchars($mascota['descripcion'])) : 'Esta mascota aún no tiene una descripción.' ?>
                            </p>

                            <hr>

                            <?php if ($es_adoptante): ?>
                                <!-- Acciones del adoptante -->
                                <div class="d-flex flex-wrap gap-2 align-items-start">
                                    <form method="POST" action="mascota.php?id=<?= $id_mascota ?>">
                                        <input type="hidden" name="accion" value="favorito">
                                        <?php if ($es_favorita): ?>
                                            <button type="submit" class="btn btn-outline-danger">&hearts; Quitar de favoritos</button>
                                        <?php else: ?>
                                            <button type="submit" class="btn btn-outline-dark">&hearts; Guardar en favoritos</button>
                                        <?php endif; ?>
                                    </form>

                                    <?php if ($adoptada_por_mi): ?>
                                        <span class="badge bg-success align-self-center py-2">¡Ya es tu mascota!</span>
                                        <a href="mis_adopciones.php" class="btn btn-dark">Ver mis adopciones</a>
                                    <?php elseif ($mascota['estado'] !== 'Disponible'): ?>
                                        <span class="text-muted small align-self-center">
                                            Esta mascota está <?= htmlspecialchars(mb_strtolower($mascota['estado'])) ?> y no recibe nuevas solicitudes.
                                        </span>
                                    <?php elseif ($solicitud_pendiente): ?>
                                        <span class="badge bg-warning text-dark align-self-center py-2">Solicitud pendiente de revisión</span>
                                    <?php elseif (!$perfil): ?>
                                        <a href="perfil.php" class="btn btn-dark">Completar mi perfil para adoptar</a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalSolicitud">
                                            Solicitar adopción
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <?php if (!$perfil && $mascota['estado'] === 'Disponible' && !$solicitud_pendiente): ?>
                                    <p class="text-muted small mt-3 mb-0">
                                        Para enviar una solicitud primero debes llenar el cuestionario de tu perfil de adoptante.
                                    </p>
                                <?php endif; ?>

                            <?php elseif ($con_sesion): ?>
                                <!-- Personal del albergue o administrador -->
                                <p class="text-muted small mb-0">
                                    Las opciones de favoritos y solicitud de adopción están disponibles solo para adoptantes.
                                </p>
                            <?php else: ?>
                                <!-- Visitante sin sesión -->
                                <div class="alert alert-light border small mb-0">
                                    Para guardar esta mascota en favoritos o solicitar su adopción necesitas una cuenta de adoptante.
                                    <div class="mt-2 d-flex gap-2">
                                        <a href="login.php?tab=registro" class="btn btn-outline-primary btn-sm">Crear Cuenta</a>
                                        <a href="login.php" class="btn btn-primary btn-sm">Login</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php if ($mascota && $es_adoptante && $perfil && $mascota['estado'] === 'Disponible' && !$solicitud_pendiente): ?>
        <!-- Modal para enviar la solicitud de adopción -->
        <div class="modal fade" id="modalSolicitud" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="mascota.php?id=<?= $id_mascota ?>" class="modal-content">
                    <input type="hidden" name="accion" value="solicitar">

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Solicitar adopción de <?= htmlspecialchars($mascota['nombre']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <label class="form-label small fw-semibold">¿Por qué quieres adoptar a <?= htmlspecialchars($mascota['nombre']) ?>? *</label>
                        <textarea name="motivo" class="form-control" rows="5" maxlength="1000" required><?= htmlspecialchars($perfil['motivo_adopcion']) ?></textarea>
                        <div class="form-text">
                            El personal del albergue revisará tu solicitud junto con tu perfil de adoptante.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-dark">Enviar solicitud</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



