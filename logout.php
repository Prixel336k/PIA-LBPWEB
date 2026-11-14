<?php
// Cierra la sesión activa del usuario y redirige al inicio
session_start();
session_unset();
session_destroy();

header("Location: index.php");
exit;
?>
