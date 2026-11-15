<?php
// Pantalla "Perfil del Adoptante": cuestionario obligatorio para poder solicitar adopciones
require_once 'config/bd.php';
require_once 'config/sesion.php';

// Solo pueden entrar usuarios con sesión iniciada
verificar_sesion();

// Este formulario solo aplica al rol Adoptante (3)
if ($_SESSION['id_rol'] != 3) {
    header("Location: index.php");
    exit;
}

$id_usuario    = $_SESSION['id_usuario'];
$mensaje_exito = "";
$errores       = [];

// Valores por defecto del formulario (vacío si el adoptante aún no tiene perfil)
$perfil = [
    'ocupacion'            => '',
    'edad'                 => '',
    'tipo_vivienda'        => '',
    'tiene_otras_mascotas' => '',
    'horas_solo_mascota'   => '',
    'ha_tenido_mascotas'   => '',
    'motivo_adopcion'      => '',
];

// Busca si el adoptante ya tiene un perfil guardado
$sql  = "SELECT ocupacion, edad, tipo_vivienda, tiene_otras_mascotas, horas_solo_mascota,
                ha_tenido_mascotas, motivo_adopcion
         FROM perfiles_adoptante WHERE id_usuario = ? LIMIT 1";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$perfil_guardado = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$ya_tiene_perfil = ($perfil_guardado !== null && $perfil_guardado !== false);

if ($ya_tiene_perfil) {
    foreach ($perfil as $campo => $vacio) {
        $perfil[$campo] = (string)$perfil_guardado[$campo];
    }
}

// Procesa el formulario cuando se envía (INSERT si es nuevo, UPDATE si ya existía)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $perfil['ocupacion']            = trim($_POST['ocupacion'] ?? '');
    $perfil['edad']                 = trim($_POST['edad'] ?? '');
    $perfil['tipo_vivienda']        = $_POST['tipo_vivienda'] ?? '';
    $perfil['tiene_otras_mascotas'] = $_POST['tiene_otras_mascotas'] ?? '';
    $perfil['horas_solo_mascota']   = trim($_POST['horas_solo_mascota'] ?? '');
    $perfil['ha_tenido_mascotas']   = $_POST['ha_tenido_mascotas'] ?? '';
    $perfil['motivo_adopcion']      = trim($_POST['motivo_adopcion'] ?? '');

    // Validaciones
    if ($perfil['ocupacion'] === '' || mb_strlen($perfil['ocupacion']) > 80) {
        $errores[] = "Escribe tu ocupación (máximo 80 caracteres).";
    }
    if (!ctype_digit($perfil['edad']) || (int)$perfil['edad'] < 18 || (int)$perfil['edad'] > 99) {
        $errores[] = "La edad debe ser un número entre 18 y 99 años.";
    }
    if (!in_array($perfil['tipo_vivienda'], ['Casa', 'Departamento'])) {
        $errores[] = "Selecciona el tipo de vivienda.";
    }
    if (!in_array($perfil['tiene_otras_mascotas'], ['0', '1'])) {
        $errores[] = "Indica si tienes otras mascotas.";
    }
    if (!ctype_digit($perfil['horas_solo_mascota']) || (int)$perfil['horas_solo_mascota'] > 24) {
        $errores[] = "Las horas que estará sola la mascota deben ser un número entre 0 y 24.";
    }
    if (!in_array($perfil['ha_tenido_mascotas'], ['0', '1'])) {
        $errores[] = "Indica si has tenido mascotas antes.";
    }
    if ($perfil['motivo_adopcion'] === '') {
        $errores[] = "Cuéntanos por qué deseas adoptar.";
    }

    if (count($errores) === 0) {
        $ocupacion = $perfil['ocupacion'];
        $edad      = (int)$perfil['edad'];
        $vivienda  = $perfil['tipo_vivienda'];
        $otras     = (int)$perfil['tiene_otras_mascotas'];
        $horas     = (int)$perfil['horas_solo_mascota'];
        $tenido    = (int)$perfil['ha_tenido_mascotas'];
        $motivo    = $perfil['motivo_adopcion'];

        try {
            if ($ya_tiene_perfil) {
                // UPDATE: el adoptante ya tenía perfil
                $sql_upd = "UPDATE perfiles_adoptante
                            SET ocupacion = ?, edad = ?, tipo_vivienda = ?, tiene_otras_mascotas = ?,
                                horas_solo_mascota = ?, ha_tenido_mascotas = ?, motivo_adopcion = ?
                            WHERE id_usuario = ?";
                $stmt_upd = mysqli_prepare($conexion, $sql_upd);
                mysqli_stmt_bind_param($stmt_upd, "sisiiisi", $ocupacion, $edad, $vivienda, $otras, $horas, $tenido, $motivo, $id_usuario);
                mysqli_stmt_execute($stmt_upd);
                $mensaje_exito = "Tu perfil se actualizó correctamente.";
            } else {
                // INSERT: primer perfil del adoptante, después va al catálogo
                $sql_ins = "INSERT INTO perfiles_adoptante
                            (id_usuario, ocupacion, edad, tipo_vivienda, tiene_otras_mascotas,
                             horas_solo_mascota, ha_tenido_mascotas, motivo_adopcion)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_ins = mysqli_prepare($conexion, $sql_ins);
                mysqli_stmt_bind_param($stmt_ins, "isisiiis", $id_usuario, $ocupacion, $edad, $vivienda, $otras, $horas, $tenido, $motivo);
                mysqli_stmt_execute($stmt_ins);

                header("Location: adoptar.php");
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            $errores[] = "Ocurrió un error al guardar tu perfil. Inténtalo de nuevo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil de Adoptante - PetMatch</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <!-- Barra de navegación superior -->
    <?php include 'includes/navbar.php'; ?>

    <main class="container py-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold">PERFIL DEL ADOPTANTE</h2>
            <p class="fst-italic text-muted mb-1">"Ayúdanos a conocerte para encontrar a tu compañero compatible"</p>
            <p class="small mb-0">Para poder enviar solicitudes de adopción, por favor completa tu perfil:</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">

                <?php if ($mensaje_exito !== ""): ?>
                    <div class="alert alert-success py-2 small">
                        <?= htmlspecialchars($mensaje_exito) ?>
                        <a href="adoptar.php" class="alert-link">Ir a buscar mascotas</a>
                    </div>
                <?php endif; ?>

                <?php if (count($errores) > 0): ?>
                    <div class="alert alert-danger py-2 small">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errores as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" action="perfil.php">

                            <!-- 1. Datos Generales -->
                            <h6 class="fw-bold border-bottom pb-2 mb-3">1. Datos Generales</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-8">
                                    <label class="form-label small fw-semibold">Ocupación *</label>
                                    <input type="text" name="ocupacion" class="form-control" maxlength="80"
                                           value="<?= htmlspecialchars($perfil['ocupacion']) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Edad *</label>
                                    <div class="input-group">
                                        <input type="number" name="edad" class="form-control" min="18" max="99"
                                               value="<?= htmlspecialchars($perfil['edad']) ?>" required>
                                        <span class="input-group-text">años</span>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Estilo de Vida -->
                            <h6 class="fw-bold border-bottom pb-2 mb-3">2. Estilo de Vida</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold d-block">¿Tipo de vivienda? *</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_vivienda" id="viv_casa" value="Casa"
                                           <?= $perfil['tipo_vivienda'] === 'Casa' ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="viv_casa">Casa</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_vivienda" id="viv_depto" value="Departamento"
                                           <?= $perfil['tipo_vivienda'] === 'Departamento' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="viv_depto">Departamento</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold d-block">¿Tienes otras mascotas? *</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tiene_otras_mascotas" id="otras_si" value="1"
                                           <?= $perfil['tiene_otras_mascotas'] === '1' ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="otras_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tiene_otras_mascotas" id="otras_no" value="0"
                                           <?= $perfil['tiene_otras_mascotas'] === '0' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="otras_no">No</label>
                                </div>
                            </div>

                            <div class="mb-4 col-md-6">
                                <label class="form-label small fw-semibold">¿Cuántas horas pasará sola la mascota? *</label>
                                <div class="input-group">
                                    <input type="number" name="horas_solo_mascota" class="form-control" min="0" max="24"
                                           value="<?= htmlspecialchars($perfil['horas_solo_mascota']) ?>" required>
                                    <span class="input-group-text">horas al día</span>
                                </div>
                            </div>

                            <!-- 3. Experiencia -->
                            <h6 class="fw-bold border-bottom pb-2 mb-3">3. Experiencia</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold d-block">¿Has tenido mascotas antes? *</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="ha_tenido_mascotas" id="tenido_si" value="1"
                                           <?= $perfil['ha_tenido_mascotas'] === '1' ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="tenido_si">Sí</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="ha_tenido_mascotas" id="tenido_no" value="0"
                                           <?= $perfil['ha_tenido_mascotas'] === '0' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tenido_no">No</label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold">¿Por qué deseas adoptar? *</label>
                                <textarea name="motivo_adopcion" class="form-control" rows="4"
                                          placeholder="Escribe aquí..." required><?= htmlspecialchars($perfil['motivo_adopcion']) ?></textarea>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-dark px-5 py-2">Guardar Mi Perfil</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
