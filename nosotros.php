<?php
// Página informativa sobre el refugio, instalaciones y estadísticas
session_start();
require_once 'config/bd.php';

// Consultas para las tarjetas de estadísticas
$total_mascotas = 0;
$total_solicitudes = 0;

if (isset($conexion)) {
    // Total de mascotas registradas
    $res_mascotas = mysqli_query($conexion, "SELECT COUNT(*) as total FROM mascotas WHERE activo = 1");
    if ($res_mascotas) {
        $row = mysqli_fetch_assoc($res_mascotas);
        $total_mascotas = $row['total'];
    }

    // Total de solicitudes de adopción
    $res_solicitudes = mysqli_query($conexion, "SELECT COUNT(*) as total FROM solicitudes_adopcion");
    if ($res_solicitudes) {
        $row = mysqli_fetch_assoc($res_solicitudes);
        $total_solicitudes = $row['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nosotros - PetMatch</title>
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
            <h1 class="fw-bold">Sobre Nosotros</h1>
            <p class="text-muted lead">Conoce más sobre nuestra misión, historia y el equipo detrás de PetMatch.</p>
        </div>

        <!-- Galería de las instalaciones -->
        <div class="mb-5">
            <h3 class="fw-bold text-center mb-4">Nuestras Instalaciones</h3>
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <img src="assets/img/descanso.jpg" class="card-img-top object-fit-cover" style="height: 200px;" alt="Área de descanso">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-0">Áreas de Descanso</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <img src="assets/img/consultorio.jpg" class="card-img-top object-fit-cover" style="height: 200px;" alt="Consultorio veterinario">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-0">Consultorio Veterinario</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <img src="assets/img/recepcion.jpg" class="card-img-top object-fit-cover" style="height: 200px;" alt="Recepción">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-0">Recepción</h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <img src="assets/img/recreacion.jpg" class="card-img-top object-fit-cover" style="height: 200px;" alt="Patio de recreación">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-0">Patio de Recreación y Ejercicio</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <img src="assets/img/bodega.jpg" class="card-img-top object-fit-cover" style="height: 200px;" alt="Bodega de alimento">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-0">Bodega de Alimento y Suministros</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <img src="assets/img/adopcion.jpeg" class="card-img-top object-fit-cover" style="height: 200px;" alt="Área de adopción">
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-0">Área de Adopción y Convivencia</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas de estadísticas conectadas a la BD -->
        <div class="row text-center mb-5">
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-0 py-3">
                    <div class="card-body">
                        <h2 class="fw-bold text-primary"><?= (int)$total_mascotas ?></h2>
                        <p class="text-muted mb-0">Mascotas en el Refugio</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-0 py-3">
                    <div class="card-body">
                        <h2 class="fw-bold text-success">100%</h2>
                        <p class="text-muted mb-0">Compromiso y Cuidado</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-0 py-3">
                    <div class="card-body">
                        <h2 class="fw-bold text-warning"><?= (int)$total_solicitudes ?></h2>
                        <p class="text-muted mb-0">Solicitudes Gestionadas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historia del albergue -->
        <div class="row align-items-center mb-5 bg-white p-4 rounded shadow-sm">
            <div class="col-lg-6 mb-3 mb-lg-0">
                <h3 class="fw-bold mb-3">Nuestra Historia</h3>
                <p class="text-muted">
                    PetMatch nació con la firme convicción de transformar la vida de los animales en situación de calle y abandono.
                    Nuestro albergue provee un espacio seguro, atención veterinaria integral y, sobre todo, un hogar temporal lleno
                    de amor mientras cada mascota encuentra a su familia ideal.
                </p>
                <p class="text-muted mb-0">
                    Facilitamos el proceso de emparejamiento entre adoptantes y animales de forma transparente, asegurando que cada
                    adopción sea responsable y exitosa para ambas partes.
                </p>
            </div>
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&w=800&q=80" alt="Refugio de animales" class="img-fluid rounded shadow-sm">
            </div>
        </div>

        <!-- Nuestro equipo -->
        <div class="mb-5">
            <h3 class="fw-bold text-center mb-4">Nuestro Equipo</h3>
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80" class="rounded-circle mx-auto mb-3 object-fit-cover" style="width: 120px; height: 120px;" alt="Directora">
                        <h5 class="fw-bold mb-1">Dra. Sofía Martínez</h5>
                        <p class="text-muted small mb-0">Directora General y Veterinaria</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=300&q=80" class="rounded-circle mx-auto mb-3 object-fit-cover" style="width: 120px; height: 120px;" alt="Coordinador">
                        <h5 class="fw-bold mb-1">Carlos Garza</h5>
                        <p class="text-muted small mb-0">Coordinador de Adopciones</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3">
                        <img src="https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=300&q=80" class="rounded-circle mx-auto mb-3 object-fit-cover" style="width: 120px; height: 120px;" alt="Especialista">
                        <h5 class="fw-bold mb-1">Lucía Fernández</h5>
                        <p class="text-muted small mb-0">Especialista en Conducta Animal</p>
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
