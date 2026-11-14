<?php
// Pantalla de inicio de sesión y creación de cuenta (Adoptante)
session_start();
require_once 'config/bd.php';

$mensaje_error = "";
$mensaje_exito = "";

// Determina qué pestaña mostrar activa ('login' o 'registro')
$tab_activa = (isset($_GET['tab']) && $_GET['tab'] === 'registro') ? 'registro' : 'login';

// Procesa el formulario cuando el usuario envía datos por método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // Iniciar sesión
    if ($accion === 'login') {
        $tab_activa = 'login';
        $correo   = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';

        // Busca al usuario por su correo
        $sql  = "SELECT id_usuario, id_rol, nombre_completo, correo, contrasena_hash 
                 FROM usuarios WHERE correo = ? LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "s", $correo);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $usuario   = mysqli_fetch_assoc($resultado);

        // Verifica si existe el usuario y si la contraseña coincide con el hash
        if ($usuario && password_verify($password, $usuario['contrasena_hash'])) {
            $_SESSION['id_usuario']      = $usuario['id_usuario'];
            $_SESSION['id_rol']          = $usuario['id_rol'];
            $_SESSION['nombre_completo'] = $usuario['nombre_completo'];

            header("Location: index.php");
            exit;
        } else {
            $mensaje_error = "El correo o la contraseña son incorrectos.";
        }
    }

    // Crear cuenta (registra con rol 3 = Adoptante)
    if ($accion === 'registro') {
        $tab_activa = 'registro';
        $nombre   = trim($_POST['nombre_completo'] ?? '');
        $correo   = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($nombre === '' || $correo === '' || $password === '') {
            $mensaje_error = "Por favor completa todos los campos obligatorios.";
        } else {
            // Revisa que el correo no esté registrado todavía
            $sql_check  = "SELECT id_usuario FROM usuarios WHERE correo = ? LIMIT 1";
            $stmt_check = mysqli_prepare($conexion, $sql_check);
            mysqli_stmt_bind_param($stmt_check, "s", $correo);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);

            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $mensaje_error = "Este correo electrónico ya está registrado.";
            } else {
                // Encripta la contraseña con password_hash y guarda como Adoptante (id_rol = 3)
                $hash_nuevo = password_hash($password, PASSWORD_DEFAULT);
                $id_rol_adoptante = 3;

                $sql_insert = "INSERT INTO usuarios (id_rol, nombre_completo, correo, contrasena_hash) 
                               VALUES (?, ?, ?, ?)";
                $stmt_ins = mysqli_prepare($conexion, $sql_insert);
                mysqli_stmt_bind_param($stmt_ins, "isss", $id_rol_adoptante, $nombre, $correo, $hash_nuevo);

                if (mysqli_stmt_execute($stmt_ins)) {
                    // Inicia la sesión automáticamente al registrarse
                    $_SESSION['id_usuario']      = mysqli_insert_id($conexion);
                    $_SESSION['id_rol']          = $id_rol_adoptante;
                    $_SESSION['nombre_completo'] = $nombre;

                    header("Location: index.php");
                    exit;
                } else {
                    $mensaje_error = "Ocurrió un error al registrar la cuenta.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión / Crear Cuenta - PetMatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <?php include 'includes/navbar.php'; ?>

    <main class="container my-auto py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm border-0">
                    <!-- Pestañas superiores: Iniciar sesión | Crear cuenta -->
                    <div class="card-header bg-white p-0 border-bottom">
                        <ul class="nav nav-tabs nav-fill border-0" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-3 fw-semibold <?= $tab_activa === 'login' ? 'active' : '' ?>"
                                        data-bs-toggle="tab" data-bs-target="#panel-login" type="button">
                                    Iniciar sesión
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-3 fw-semibold <?= $tab_activa === 'registro' ? 'active' : '' ?>"
                                        data-bs-toggle="tab" data-bs-target="#panel-registro" type="button">
                                    Crear cuenta
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-4">
                        <!-- Alertas de error -->
                        <?php if ($mensaje_error !== ""): ?>
                            <div class="alert alert-danger py-2 small"><?= htmlspecialchars($mensaje_error) ?></div>
                        <?php endif; ?>

                        <div class="tab-content">
                            <!-- Iniciar sesión -->
                            <div class="tab-pane fade <?= $tab_activa === 'login' ? 'show active' : '' ?>" id="panel-login">
                                <form method="POST" action="login.php">
                                    <input type="hidden" name="accion" value="login">

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Correo electrónico *</label>
                                        <input type="email" name="correo" class="form-control" placeholder="correo@ejemplo.com" required>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-semibold small">Contraseña *</label>
                                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                                    </div>

                                    <button type="submit" class="btn btn-dark w-100 py-2">
                                        Iniciar sesión
                                    </button>
                                </form>
                            </div>

                            <!-- Crear cuenta -->
                            <div class="tab-pane fade <?= $tab_activa === 'registro' ? 'show active' : '' ?>" id="panel-registro">
                                <form method="POST" action="login.php?tab=registro">
                                    <input type="hidden" name="accion" value="registro">

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Nombre completo *</label>
                                        <input type="text" name="nombre_completo" class="form-control" placeholder="Ej. Juan Pérez" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Correo electrónico *</label>
                                        <input type="email" name="correo" class="form-control" placeholder="correo@ejemplo.com" required>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-semibold small">Contraseña *</label>
                                        <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" minlength="6" required>
                                    </div>

                                    <button type="submit" class="btn btn-dark w-100 py-2">
                                        Crear cuenta
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
