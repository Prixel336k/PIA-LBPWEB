<?php
// Gestión de mascotas del albergue (Listado, filtros y registro de nuevas mascotas)
require_once '../config/bd.php';
require_once '../config/sesion.php';

// Solo Personal del Albergue (2) y Administrador (1) pueden entrar
verificar_staff();

$mensaje_exito = "";
$mensaje_error = "";

// Procesa el formulario para registrar una nueva mascota (CREATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear') {
    $nombre      = trim($_POST['nombre'] ?? '');
    $especie     = $_POST['especie'] ?? 'Perro';
    $raza        = trim($_POST['raza'] ?? 'Mestizo');
    $sexo        = $_POST['sexo'] ?? 'Macho';
    $tamano      = $_POST['tamano'] ?? 'Mediano';
    $edad_meses  = (int)($_POST['edad_meses'] ?? 1);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $estado      = $_POST['estado'] ?? 'Disponible';
    $id_publicado_por = $_SESSION['id_usuario'];

    if ($raza === '') {
        $raza = 'Mestizo';
    }

    // Ruta por defecto si no se sube imagen
    $ruta_foto = 'img/sin-foto.jpg';

    // Si el usuario seleccionó un archivo de imagen, lo guardamos en uploads/mascotas/
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $nombre_archivo = time() . '_' . basename($_FILES['foto']['name']);
        $destino = '../uploads/mascotas/' . $nombre_archivo;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
            $ruta_foto = 'uploads/mascotas/' . $nombre_archivo;
        }
    }

    if ($nombre === '') {
        $mensaje_error = "El nombre de la mascota es obligatorio.";
    } else {
        $sql_ins = "INSERT INTO mascotas 
                    (id_publicado_por, nombre, especie, raza, sexo, tamano, edad_meses, descripcion, foto, estado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conexion, $sql_ins);
        mysqli_stmt_bind_param(
            $stmt,
            "isssssisss",
            $id_publicado_por,
            $nombre,
            $especie,
            $raza,
            $sexo,
            $tamano,
            $edad_meses,
            $descripcion,
            $ruta_foto,
            $estado
        );

        if (mysqli_stmt_execute($stmt)) {
            $mensaje_exito = "Mascota registrada correctamente.";
        } else {
            $mensaje_error = "Error al guardar la mascota.";
        }
    }
}

// Parámetros de búsqueda y filtro por estado (READ)
$buscar        = trim($_GET['buscar'] ?? '');
$filtro_estado = $_GET['estado'] ?? 'Todas';

$sql = "SELECT id_mascota, nombre, especie, raza, sexo, tamano, edad_meses, descripcion, estado, fecha_publicacion 
        FROM mascotas 
        WHERE activo = 1";

if ($buscar !== '') {
    $buscar_seguro = mysqli_real_escape_string($conexion, $buscar);
    $sql .= " AND nombre LIKE '%$buscar_seguro%'";
}

if ($filtro_estado !== 'Todas' && in_array($filtro_estado, ['Disponible', 'En proceso', 'Adoptada'])) {
    $estado_seguro = mysqli_real_escape_string($conexion, $filtro_estado);
    $sql .= " AND estado = '$estado_seguro'";
}

$sql .= " ORDER BY id_mascota DESC";
$resultado_mascotas = mysqli_query($conexion, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - Mascotas</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container-fluid">
        <div class="row">
            <!-- Menú lateral del panel administrativo -->
            <?php include '../includes/sidebar_admin.php'; ?>

            <!-- Contenido principal -->
            <main class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h3 class="fw-bold mb-0">Mascotas</h3>
                        <p class="text-muted small mb-0">Crear, editar y eliminar los perfiles de animales disponibles para adopción.</p>
                    </div>
                    <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalAgregarMascota">
                        + Agregar mascota
                    </button>
                </div>

                <?php if ($mensaje_exito !== ""): ?>
                    <div class="alert alert-success py-2 small"><?= htmlspecialchars($mensaje_exito) ?></div>
                <?php endif; ?>
                <?php if ($mensaje_error !== ""): ?>
                    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($mensaje_error) ?></div>
                <?php endif; ?>

                <!-- Barra de búsqueda y botones de filtro por estado -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-2 align-items-center justify-content-between">
                            <div class="col-md-5">
                                <form method="GET" action="mascotas.php" class="d-flex gap-2">
                                    <input type="hidden" name="estado" value="<?= htmlspecialchars($filtro_estado) ?>">
                                    <input type="text" name="buscar" class="form-control form-control-sm"
                                           placeholder="Buscar por nombre..." value="<?= htmlspecialchars($buscar) ?>">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">Buscar</button>
                                </form>
                            </div>

                            <div class="col-md-7 text-md-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="mascotas.php?estado=Todas"
                                       class="btn <?= $filtro_estado === 'Todas' ? 'btn-dark' : 'btn-outline-secondary' ?>">Todas</a>
                                    <a href="mascotas.php?estado=Disponible"
                                       class="btn <?= $filtro_estado === 'Disponible' ? 'btn-dark' : 'btn-outline-secondary' ?>">Disponibles</a>
                                    <a href="mascotas.php?estado=En+proceso"
                                       class="btn <?= $filtro_estado === 'En proceso' ? 'btn-dark' : 'btn-outline-secondary' ?>">En proceso</a>
                                    <a href="mascotas.php?estado=Adoptada"
                                       class="btn <?= $filtro_estado === 'Adoptada' ? 'btn-dark' : 'btn-outline-secondary' ?>">Adoptadas</a>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de registros de mascotas -->
                        <div class="table-responsive mt-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Edad</th>
                                        <th>Estado</th>
                                        <th>Publicado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($resultado_mascotas) > 0): ?>
                                        <?php while ($m = mysqli_fetch_assoc($resultado_mascotas)): ?>
                                            <?php
                                            // Convierte los meses a texto legible (años o meses)
                                            $meses = (int)$m['edad_meses'];
                                            if ($meses >= 12) {
                                                $anios = floor($meses / 12);
                                                $edad_texto = $anios . ($anios == 1 ? ' año' : ' años');
                                            } else {
                                                $edad_texto = $meses . ($meses == 1 ? ' mes' : ' meses');
                                            }

                                            // Color de la etiqueta según el estado
                                            $clase_badge = 'bg-success';
                                            if ($m['estado'] === 'En proceso') $clase_badge = 'bg-warning text-dark';
                                            if ($m['estado'] === 'Adoptada')   $clase_badge = 'bg-secondary';
                                            ?>
                                            <tr>
                                                <td class="fw-semibold"><?= htmlspecialchars($m['nombre']) ?></td>
                                                <td><?= htmlspecialchars($m['especie'] . ' - ' . $m['tamano']) ?></td>
                                                <td><?= $edad_texto ?></td>
                                                <td>
                                                    <span class="badge <?= $clase_badge ?>">
                                                        <?= htmlspecialchars($m['estado']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($m['fecha_publicacion'])) ?></td>
                                                <td>
                                                    <button class="btn btn-outline-secondary btn-sm" disabled>Editar</button>
                                                    <button class="btn btn-outline-danger btn-sm" disabled>Eliminar</button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                No se encontraron mascotas registradas con esos filtros.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para Agregar Nueva Mascota -->
    <div class="modal fade" id="modalAgregarMascota" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="mascotas.php" enctype="multipart/form-data" class="modal-content">
                <input type="hidden" name="accion" value="crear">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Agregar nueva mascota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Especie *</label>
                            <select name="especie" class="form-select">
                                <option value="Perro">Perro</option>
                                <option value="Gato">Gato</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Raza</label>
                            <input type="text" name="raza" class="form-control" placeholder="Ej. Mestizo">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Sexo *</label>
                            <select name="sexo" class="form-select">
                                <option value="Macho">Macho</option>
                                <option value="Hembra">Hembra</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tamaño *</label>
                            <select name="tamano" class="form-select">
                                <option value="Pequeño">Pequeño</option>
                                <option value="Mediano" selected>Mediano</option>
                                <option value="Grande">Grande</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Edad (en meses) *</label>
                            <input type="number" name="edad_meses" class="form-control" min="1" max="240" value="12" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Fotografía</label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3" placeholder="Carácter, historia o cuidados..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark">Guardar mascota</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
