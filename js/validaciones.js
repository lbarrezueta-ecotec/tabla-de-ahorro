function validarCrearSala(form) {
    // Solo crea la sala inmediatamente sin más datos (fallback para tests)
    return true;
}

function validarCrearSalaIndividual(form) {
    const nombre = form.nombre_sala ? form.nombre_sala.value.trim() : '';
    if (!nombre || nombre.length < 3 || nombre.length > 100) {
        alert('El nombre de la sala debe tener entre 3 y 100 caracteres');
        return false;
    }
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

function validarMeta(valor) {
    const num = parseFloat(valor);
    if (isNaN(num)) {
        alert('La meta debe ser un valor numérico');
        return false;
    }
    if (num < 10 || num > 100000) {
        alert('La meta debe estar entre 10 y 100000');
        return false;
    }
    if (num % 5 !== 0) {
        alert('La meta debe ser múltiplo de 5');
        return false;
    }
    return true;
}
