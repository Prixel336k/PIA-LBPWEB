<?php
// Página de contacto, ubicación del albergue y envío de dudas
session_start();
require_once 'config/bd.php';

$mensaje_enviado = false;

// Verifica si el formulario fue enviado por método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje_enviado = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto - PetMatch</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Barra de navegación superior -->
    <?php include 'includes/navbar.php'; ?>

    <main class="container my-5 flex-grow-1">
        <!-- Encabezado de la sección -->
        <div class="text-center mb-5">
            <h1 class="fw-bold">Contáctanos y Ubicación</h1>
            <p class="text-muted lead">¿Tienes dudas sobre el proceso de adopción o deseas visitarnos? Escríbenos.</p>
        </div>

        <?php if ($mensaje_enviado): ?>
            <!-- Alerta de confirmación de envío -->
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                <h5 class="alert-heading fw-bold mb-1">¡Mensaje enviado con éxito!</h5>
                <p class="mb-0 small">Tu duda o comentario ha sido enviado al refugio correctamente. Nos pondremos en contacto contigo a la brevedad.</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Formulario de contacto -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm p-4 h-100">
                    <h3 class="fw-bold mb-3">Envíanos un Mensaje</h3>
                    <form action="contacto.php" method="POST">
                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-semibold">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej. Roberto Hernández" required>
                        </div>
                        <div class="mb-3">
                            <label for="correo" class="form-label fw-semibold">Correo Electrónico</label>
                            <input type="email" class="form-control" id="correo" name="correo" placeholder="correo@ejemplo.com" required>
                        </div>
                        <div class="mb-3">
                            <label for="asunto" class="form-label fw-semibold">Asunto</label>
                            <input type="text" class="form-control" id="asunto" name="asunto" placeholder="Ej. Estatus de solicitud de adopción" required>
                        </div>
                        <div class="mb-3">
                            <label for="mensaje" class="form-label fw-semibold">Mensaje</label>
                            <textarea class="form-control" id="mensaje" name="mensaje" rows="4" placeholder="Escribe tu duda o comentario aquí..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 fw-semibold py-2">Enviar Duda</button>
                    </form>
                </div>
            </div>

            <!-- Información de ubicación y datos de contacto -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h3 class="fw-bold mb-3">Información del Refugio</h3>
                        <p class="text-muted mb-4">
                            Estamos ubicados en la zona metropolitana listos para recibirte y orientarte en la búsqueda de tu nuevo compañero.
                        </p>

                        <ul class="list-unstyled mb-4">
                            <li class="mb-3 d-flex align-items-start">
                                <span class="fw-bold me-2">Dirección:</span>
                                <span class="text-muted">Av. Universidad S/N, Cd. Universitaria, San Nicolás de los Garza, N.L.</span>
                            </li>
                            <li class="mb-3 d-flex align-items-start">
                                <span class="fw-bold me-2">Teléfono:</span>
                                <span class="text-muted">+52 (81) 1234-5678</span>
                            </li>
                            <li class="mb-3 d-flex align-items-start">
                                <span class="fw-bold me-2">Correo:</span>
                                <span class="text-muted">contacto@petmatch.com</span>
                            </li>
                            <li class="d-flex align-items-start">
                                <span class="fw-bold me-2">Horarios:</span>
                                <span class="text-muted">Lunes a Sábado de 9:00 AM a 6:00 PM</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Mapa de ubicación -->
                    <div class="rounded overflow-hidden shadow-sm" style="height: 200px; background-color: #e9ecef;">
                        <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted fw-semibold">
                            [Mapa de Ubicación del Refugio - Ciudad Universitaria]
                        </div>
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
