function validarCrearSala(form) {
    // Solo crea la sala inmediatamente sin más datos
    return true;
}

function validarEntrarSala(form) {
    const val = form.codigo.value.trim();
    if (!val) {
        alert('Ingresá el código de sala');
        return false;
    }
    if (val.length < 3 || val.length > 10) {
        alert('El código debe tener entre 3 y 10 caracteres');
        return false;
    }
    return true;
}

function validarRegistroUsuario(form) {
    const nombre = form.nombre.value.trim();
    const contrasena = form.contrasena.value;
    if (!nombre || nombre.length < 3 || nombre.length > 50) {
        alert('El nombre debe tener entre 3 y 50 caracteres');
        return false;
    }
    if (!contrasena || contrasena.length < 4) {
        alert('La contraseña debe tener al menos 4 caracteres');
        return false;
    }
    return true;
}

function validarLoginUsuario(form) {
    const nombre = form.nombre.value.trim();
    const contrasena = form.contrasena.value;
    if (!nombre || !contrasena) {
        alert('Completá ambos campos');
        return false;
    }
    return true;
}
