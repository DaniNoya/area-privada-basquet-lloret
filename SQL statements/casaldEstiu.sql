-- Tabla: semanas_campus
CREATE TABLE semanas_campus (
    id_semana INT PRIMARY KEY,
    temporada_id INT,
    tipo_pago_id INT,
    fecha_inicio DATE,
    fecha_fin DATE,
    precio DECIMAL(10, 2),
    FOREIGN KEY (temporada_id) REFERENCES temporada(id),
    FOREIGN KEY (tipo_pago_id) REFERENCES tipo_pago(id)
);

-- Insertar los valores de la imagen en semanas_campus
INSERT INTO semanas_campus (id_semana, temporada_id, tipo_pago_id, fecha_inicio, fecha_fin, precio)
VALUES 
(1, 7, 28, '2024-06-01', '2024-06-01', 60),
(2, 7, 28, '2024-06-01', '2024-06-01', 55),
(3, 7, 28, '2024-06-01', '2024-06-01', 55),
(4, 7, 28, '2024-06-01', '2024-06-01', 50),
(5, 7, 28, '2024-06-01', '2024-06-01', 40),
(6, 7, 28, '2024-06-01', '2024-06-01', 40),
(7, 7, 28, '2024-06-01', '2024-06-01', 40),
(8, 7, 28, '2024-06-01', '2024-06-01', 40),
(9, 7, 28, '2024-06-01', '2024-06-01', 40);

-- Tabla: jugador_semanas
CREATE TABLE jugador_semanas (
    jugador_id INT,
    semana_id INT,
    fecha_inscripcion DATE,
    PRIMARY KEY (jugador_id, semana_id),
    FOREIGN KEY (jugador_id) REFERENCES jugador(id),
    FOREIGN KEY (semana_id) REFERENCES semanas_campus(id_semana)
);

-- Insertar el nuevo concepto de pago en la tabla tipo_pago
INSERT INTO tipo_pago (id, concepto, idTemporada, borrado)
VALUES (28, "Casal d'Estiu", 7, 0);

-- Insertar modulo campus-jugador en la tabla modulos
INSERT INTO modulos (id, nombre, ruta)
VALUES (14, 'Campus', 'campus-jugador');

-- Insertar nuevo permiso en la tabla permisos
INSERT INTO permisos (idPerfil, idModulo)
VALUES (1, 14);


-- Tabla: jugador_portasobertes
CREATE TABLE jugador_portasObertes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    primer_apellido VARCHAR(100) NOT NULL,
    segundo_apellido VARCHAR(100),
    fecha_nacimiento DATE,
    email VARCHAR(100),
    telefono1 VARCHAR(20),
    telefono2 VARCHAR(20),
    genero INT,
    pastClubBool BOOLEAN DEFAULT FALSE,
    pastClubName VARCHAR(100),
    FOREIGN KEY (genero) REFERENCES sexo(id)
);
