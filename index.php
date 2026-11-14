<?php
// Página de inicio del sitio web PetMatch
session_start();
require_once 'config/bd.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetMatch - Refugio de Mascotas</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <!-- Barra de navegación superior -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Sección principal -->
    <main class="container my-auto py-5">
        <div class="bg-white border rounded-3 shadow-sm p-5 text-center my-4">
            <h1 class="display-5 fw-bold text-dark mb-3">REFUGIO DE MASCOTAS</h1>
            <p class="fs-5 text-muted mb-4">Encuentra a tu mejor amigo</p>
            <p class="col-md-8 mx-auto text-secondary mb-4">
                Facilitamos el encuentro entre animales rescatados y personas interesadas en darles un hogar.
                Explora los perfiles de nuestras mascotas disponibles, conoce su historia y envía tu propuesta de adopción.
            </p>
            <a href="adoptar.php" class="btn btn-dark btn-lg px-4">
                Explorar Mascotas
            </a>
        </div>

        <!-- Tarjetas informativas del proceso -->
        <div class="row text-center mt-5 g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-3">
                    <div class="card-body">
                        <h5 class="fw-bold">1. Crea tu cuenta</h5>
                        <p class="text-muted small mb-0">
                            Regístrate como adoptante y completa tu perfil de estilo de vida para conocerte mejor.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-3">
                    <div class="card-body">
                        <h5 class="fw-bold">2. Explora y filtra</h5>
                        <p class="text-muted small mb-0">
                            Busca perros y gatos por tamaño, edad o sexo y guarda tus perfiles favoritos.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-3">
                    <div class="card-body">
                        <h5 class="fw-bold">3. Solicita adoptar</h5>
                        <p class="text-muted small mb-0">
                            Envía tu propuesta desde el perfil de la mascota para que el personal del albergue la valide.
                        </p>
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
