CREATE TABLE IF NOT EXISTS salas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL DEFAULT 'Sala sin nombre',
    url_unica VARCHAR(255) UNIQUE NOT NULL,
    meta DECIMAL(10,2) DEFAULT 1500.00,
    modo VARCHAR(10) DEFAULT 'individual', -- 'individual' | 'grupal'
    admin_usuario_id INT NULL,
    num_personas INT DEFAULT 1,
    tipo_suma VARCHAR(20) DEFAULT 'conjunta', -- 'conjunta' | 'individual'
    valores TEXT NULL,
    creada_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sala_id INT NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sala_id) REFERENCES salas(id) ON DELETE CASCADE,
    UNIQUE KEY uq_sala_nombre (sala_id, nombre)
);

CREATE TABLE IF NOT EXISTS progreso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sala_id INT NOT NULL,
    usuario_id INT NOT NULL,
    valor_ahorrado DECIMAL(10,2) DEFAULT 0,
    casillas_marcadas INT DEFAULT 0,
    casillas_indices TEXT NULL,
    valores TEXT NULL,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sala_id) REFERENCES salas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY uq_sala_usuario (sala_id, usuario_id)
);
