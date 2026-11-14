<?php
//Conecta PHP con nuestra base de datos MySQL (petmatch_db)

$servidor   = "localhost";
$usuario_bd = "root";       
$clave_bd   = "";           // En XAMPP la contraseña viene vacía por defecto
$nombre_bd  = "petmatch_db";

// Creamos la conexión usando mysqli
$conexion = mysqli_connect($servidor, $usuario_bd, $clave_bd, $nombre_bd);

// Verifica si hubo algún error al conectar
if (!$conexion) {
    die("Error al conectar con la base de datos: " . mysqli_connect_error());
}

// Configuramos utf8mb4 para que se vean bien los acentos y la letra ñ
mysqli_set_charset($conexion, "utf8mb4");
?>
