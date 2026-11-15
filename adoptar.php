<?php
// Pantalla "Busco adoptar": catálogo dinámico de mascotas con filtros
require_once 'config/bd.php';
require_once 'config/sesion.php';
require_once 'includes/funciones_mascota.php';

// Solo los usuarios con sesión iniciada pueden filtrar; los visitantes ven el catálogo básico
$con_sesion = isset($_SESSION['id_usuario']);

// Opciones permitidas para cada filtro (sirven también para validar lo que llega por GET)
$opciones_especie = ['' => 'Todas', 'Perro' => 'Perro', 'Gato' => 'Gato', 'Otro' => 'Otro'];
$opciones_sexo    = ['' => 'Cualquiera', 'Macho' => 'Macho', 'Hembra' => 'Hembra'];
$opciones_tamano  = ['' => 'Cualquiera', 'Pequeño' => 'Pequeño', 'Mediano' => 'Mediano', 'Grande' => 'Grande'];

// Rangos de edad en meses: [texto, mínimo, máximo]
$rangos_edad = [
    'cachorro' => ['Menos de 1 año', 0, 11],
    'joven'    => ['1 a 2 años', 12, 35],
    'adulto'   => ['3 a 7 años', 36, 95],
    'senior'   => ['8 años o más', 96, 1000],
];

// Lista de razas disponibles (se llena desde la base de datos)
$opciones_raza = ['' => 'Todas'];
$res_razas = mysqli_query($conexion, "SELECT DISTINCT raza FROM mascotas
                                      WHERE activo = 1 AND estado = 'Disponible' ORDER BY raza");
while ($fila = mysqli_fetch_assoc($res_razas)) {
    $opciones_raza[$fila['raza']] = $fila['raza'];
}

// Lee los filtros por método GET (solo si hay sesión) y descarta valores no permitidos
$f_especie = $f_raza = $f_edad = $f_sexo = $f_tamano = '';
if ($con_sesion) {
    $f_especie = $_GET['especie'] ?? '';
    $f_raza    = $_GET['raza'] ?? '';
    $f_edad    = $_GET['edad'] ?? '';
    $f_sexo    = $_GET['sexo'] ?? '';
    $f_tamano  = $_GET['tamano'] ?? '';

    if (!isset($opciones_especie[$f_especie])) $f_especie = '';
    if (!isset($opciones_raza[$f_raza]))       $f_raza    = '';
    if (!isset($rangos_edad[$f_edad]))         $f_edad    = '';
    if (!isset($opciones_sexo[$f_sexo]))       $f_sexo    = '';
    if (!isset($opciones_tamano[$f_tamano]))   $f_tamano  = '';
}

// Consulta de las mascotas disponibles, agregando solo los filtros que se eligieron
$sql = "SELECT id_mascota, nombre, especie, raza, sexo, tamano, edad_meses, foto
        FROM mascotas
        WHERE activo = 1 AND estado = 'Disponible'";
$tipos   = "";
$valores = [];

if ($f_especie !== '') { $sql .= " AND especie = ?"; $tipos .= "s"; $valores[] = $f_especie; }
if ($f_raza !== '')    { $sql .= " AND raza = ?";    $tipos .= "s"; $valores[] = $f_raza; }
if ($f_sexo !== '')    { $sql .= " AND sexo = ?";    $tipos .= "s"; $valores[] = $f_sexo; }
if ($f_tamano !== '')  { $sql .= " AND tamano = ?";  $tipos .= "s"; $valores[] = $f_tamano; }
if ($f_edad !== '') {
    $sql .= " AND edad_meses BETWEEN ? AND ?";
    $tipos .= "ii";
    $valores[] = $rangos_edad[$f_edad][1];
    $valores[] = $rangos_edad[$f_edad][2];
}
$sql .= " ORDER BY fecha_publicacion DESC";

$stmt = mysqli_prepare($conexion, $sql);
if ($tipos !== "") {
    mysqli_stmt_bind_param($stmt, $tipos, ...$valores);
}
mysqli_stmt_execute($stmt);
$resultado_mascotas = mysqli_stmt_get_result($stmt);
$total = mysqli_num_rows($resultado_mascotas);

// Genera las <option> de un menú desplegable marcando la opción elegida
function opciones_select($lista, $actual) {
    foreach ($lista as $valor => $texto) {
        $marca = ((string)$valor === (string)$actual) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($valor) . '" ' . $marca . '>' . htmlspecialchars($texto) . '</option>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Busco Adoptar - PetMatch</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <!-- Barra de navegación superior -->
    <?php include 'includes/navbar.php'; ?>

    <main class="container py-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold">BUSCO ADOPTAR</h2>
            <p class="fst-italic text-muted mb-0">"Filtra y encuentra a tu compañero ideal"</p>
        </div>

        <?php if ($con_sesion): ?>
            <!-- Panel de búsqueda y filtro (solo usuarios con sesión) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Panel de Búsqueda y Filtro</h6>
                    <form method="GET" action="adoptar.php">
                        <div class="row g-3">
                            <div class="col-6 col-md">
                                <label class="form-label small fw-semibold">Especie</label>
                                <select name="especie" class="form-select">
                                    <?php opciones_select($opciones_especie, $f_especie); ?>
                                </select>
                            </div>
                            <div class="col-6 col-md">
                                <label class="form-label small fw-semibold">Raza</label>
                                <select name="raza" class="form-select">
                                    <?php opciones_select($opciones_raza, $f_raza); ?>
                                </select>
                            </div>
                            <div class="col-6 col-md">
                                <label class="form-label small fw-semibold">Edad</label>
                                <select name="edad" class="form-select">
                                    <option value="">Cualquiera</option>
                                    <?php foreach ($rangos_edad as $clave => $rango): ?>
                                        <option value="<?= $clave ?>" <?= $f_edad === $clave ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($rango[0]) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md">
                                <label class="form-label small fw-semibold">Sexo</label>
                                <select name="sexo" class="form-select">
                                    <?php opciones_select($opciones_sexo, $f_sexo); ?>
                                </select>
                            </div>
                            <div class="col-6 col-md">
                                <label class="form-label small fw-semibold">Tamaño</label>
                                <select name="tamano" class="form-select">
                                    <?php opciones_select($opciones_tamano, $f_tamano); ?>
                                </select>
                            </div>
                        </div>
                        <div class="text-center mt-4 d-flex justify-content-center gap-2">
                            <button type="submit" class="btn btn-dark px-4">BUSCAR MASCOTAS</button>
                            <a href="adoptar.php" class="btn btn-outline-secondary">Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Los visitantes ven el catálogo básico sin filtros -->
            <div class="alert alert-light border text-center small mb-4">
                Para filtrar mascotas, guardar favoritos y enviar solicitudes de adopción necesitas una cuenta.
                <a href="login.php?tab=registro" class="alert-link">Crear cuenta</a> o
                <a href="login.php" class="alert-link">Iniciar sesión</a>.
            </div>
        <?php endif; ?>

        <!-- Resultados de búsqueda -->
        <p class="fw-semibold small mb-3">
            Resultados de Búsqueda:
            <span class="text-muted fw-normal">(<?= $total ?> <?= $total == 1 ? 'mascota disponible' : 'mascotas disponibles' ?>)</span>
        </p>

        <?php if ($total > 0): ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
                <?php while ($m = mysqli_fetch_assoc($resultado_mascotas)): ?>
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="p-3 pb-0">
                                <?php mostrar_foto($m['foto'], $m['nombre'], 200, 'rounded'); ?>
                            </div>
                            <div class="card-body text-center">
                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($m['nombre']) ?></h5>
                                <p class="mb-1">Edad: <?= edad_texto($m['edad_meses']) ?></p>
                                <p class="text-muted small mb-0">
                                    <?= htmlspecialchars($m['especie'] . ' · ' . $m['raza'] . ' · ' . $m['tamano']) ?>
                                </p>
                            </div>
                            <div class="card-footer bg-white border-0 pb-3">
                                <a href="mascota.php?id=<?= (int)$m['id_mascota'] ?>" class="btn btn-outline-dark w-100">Ver perfil</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center text-muted py-5">
                    No se encontraron mascotas disponibles con esos filtros.
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



