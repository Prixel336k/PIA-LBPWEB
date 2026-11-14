<?php
// Funciones para verificar la sesión y el rol del usuario

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica que el usuario haya iniciado sesión (cualquier rol)
function verificar_sesion() {
    if (!isset($_SESSION['id_usuario'])) {
        header("Location: login.php");
        exit;
    }
}

// Verifica que el usuario sea Personal del Albergue (2) o Administrador (1)
function verificar_staff() {
    if (!isset($_SESSION['id_usuario'])) {
        header("Location: ../login.php");
        exit;
    }
    if ($_SESSION['id_rol'] != 1 && $_SESSION['id_rol'] != 2) {
        header("Location: ../index.php");
        exit;
    }
}

// Verifica que el usuario sea exclusivamente Administrador (1)
function verificar_admin() {
    if (!isset($_SESSION['id_usuario'])) {
        header("Location: ../login.php");
        exit;
    }
    if ($_SESSION['id_rol'] != 1) {
        header("Location: mascotas.php");
        exit;
    }
}
?>
