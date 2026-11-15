// Funciones de apoyo en el cliente para los modales del panel administrativo

function cargarDatosEdicionMascota(boton) {
    document.getElementById('edit_id_mascota').value  = boton.getAttribute('data-id');
    document.getElementById('edit_nombre').value      = boton.getAttribute('data-nombre');
    document.getElementById('edit_especie').value     = boton.getAttribute('data-especie');
    document.getElementById('edit_raza').value        = boton.getAttribute('data-raza');
    document.getElementById('edit_sexo').value        = boton.getAttribute('data-sexo');
    document.getElementById('edit_tamano').value      = boton.getAttribute('data-tamano');
    document.getElementById('edit_edad_meses').value  = boton.getAttribute('data-edad');
    document.getElementById('edit_estado').value      = boton.getAttribute('data-estado');
    document.getElementById('edit_descripcion').value = boton.getAttribute('data-descripcion');
}
