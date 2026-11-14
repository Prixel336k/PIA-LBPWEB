DROP DATABASE IF EXISTS petmatch_db;
CREATE DATABASE petmatch_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
USE petmatch_db;

-- Tabla 1: roles (Adoptante, Personal del Albergue, Administrador)
CREATE TABLE roles (
  id_rol      TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre_rol  VARCHAR(30)      NOT NULL,
  PRIMARY KEY (id_rol),
  UNIQUE KEY uq_roles_nombre (nombre_rol)
) ENGINE=InnoDB;

-- Tabla 2: usuarios (todas las cuentas del sistema)
CREATE TABLE usuarios (
  id_usuario      INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  id_rol          TINYINT UNSIGNED NOT NULL,
  nombre_completo VARCHAR(100)     NOT NULL,
  correo          VARCHAR(120)     NOT NULL,
  contrasena_hash VARCHAR(255)     NOT NULL,
  estado          ENUM('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
  fecha_registro  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  id_creado_por   INT UNSIGNED     NULL,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_usuarios_correo (correo),
  CONSTRAINT fk_usuarios_rol
    FOREIGN KEY (id_rol) REFERENCES roles (id_rol)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_usuarios_creado_por
    FOREIGN KEY (id_creado_por) REFERENCES usuarios (id_usuario)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabla 3: perfiles_adoptante (cuestionario del adoptante, relación 1-1)
CREATE TABLE perfiles_adoptante (
  id_perfil            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_usuario           INT UNSIGNED NOT NULL,
  ocupacion            VARCHAR(80)  NOT NULL,
  edad                 TINYINT UNSIGNED NOT NULL,
  tipo_vivienda        ENUM('Casa','Departamento') NOT NULL,
  tiene_otras_mascotas BOOLEAN      NOT NULL,
  horas_solo_mascota   TINYINT UNSIGNED NOT NULL,
  ha_tenido_mascotas   BOOLEAN      NOT NULL,
  motivo_adopcion      TEXT         NOT NULL,
  fecha_actualizacion  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id_perfil),
  UNIQUE KEY uq_perfil_usuario (id_usuario),
  CONSTRAINT fk_perfil_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla 4: mascotas (perfil de cada animal)
CREATE TABLE mascotas (
  id_mascota       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_publicado_por INT UNSIGNED NOT NULL,
  nombre           VARCHAR(50)  NOT NULL,
  especie          ENUM('Perro','Gato','Otro') NOT NULL,
  raza             VARCHAR(60)  NOT NULL DEFAULT 'Mestizo',
  sexo             ENUM('Macho','Hembra') NOT NULL,
  tamano           ENUM('Pequeño','Mediano','Grande') NOT NULL,
  edad_meses       SMALLINT UNSIGNED NOT NULL,
  descripcion      TEXT         NULL,
  foto              VARCHAR(255) NOT NULL DEFAULT 'img/sin-foto.jpg',
  estado           ENUM('Disponible','En proceso','Adoptada') NOT NULL DEFAULT 'Disponible',
  activo           BOOLEAN      NOT NULL DEFAULT TRUE,
  fecha_publicacion DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_mascota),
  KEY idx_mascotas_filtros (especie, tamano, estado),
  CONSTRAINT fk_mascotas_publicado_por
    FOREIGN KEY (id_publicado_por) REFERENCES usuarios (id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Tabla 5: favoritos (tabla intermedia, relación n-n usuarios <-> mascotas)
CREATE TABLE favoritos (
  id_usuario      INT UNSIGNED NOT NULL,
  id_mascota      INT UNSIGNED NOT NULL,
  fecha_guardado  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario, id_mascota),
  CONSTRAINT fk_fav_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_fav_mascota
    FOREIGN KEY (id_mascota) REFERENCES mascotas (id_mascota)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla 6: solicitudes_adopcion (propuestas enviadas y validadas por el staff)
CREATE TABLE solicitudes_adopcion (
  id_solicitud     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_adoptante     INT UNSIGNED NOT NULL,
  id_mascota       INT UNSIGNED NOT NULL,
  id_revisor       INT UNSIGNED NULL,
  fecha_solicitud  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  motivo           TEXT         NOT NULL,
  estado           ENUM('Pendiente','Aprobada','Rechazada') NOT NULL DEFAULT 'Pendiente',
  notas_internas   TEXT         NULL,
  fecha_resolucion DATETIME     NULL,
  PRIMARY KEY (id_solicitud),
  KEY idx_solicitudes_estado (estado, fecha_solicitud),
  CONSTRAINT fk_solicitud_adoptante
    FOREIGN KEY (id_adoptante) REFERENCES usuarios (id_usuario)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_solicitud_mascota
    FOREIGN KEY (id_mascota) REFERENCES mascotas (id_mascota)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_solicitud_revisor
    FOREIGN KEY (id_revisor) REFERENCES usuarios (id_usuario)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. DATOS DE EJEMPLO
-- ---------------------------------------------------------------------

INSERT INTO roles (nombre_rol) VALUES
  ('Administrador'), ('Personal del Albergue'), ('Adoptante');

INSERT INTO usuarios (id_rol, nombre_completo, correo, contrasena_hash, estado, fecha_registro, id_creado_por) VALUES
  (1, 'Administrador PetMatch', 'admin@petmatch.com',        '$2y$10$cCpJAIFxWlNy42VprlSmXO6oyu/cqzOfYVS8m5m7tAMS3ktvTP3R.', 'Activo', '2025-01-10 09:00:00', NULL),
  (2, 'Diego Fuentes',          'diego.fuentes@correo.com',  '$2y$10$cCpJAIFxWlNy42VprlSmXO6oyu/cqzOfYVS8m5m7tAMS3ktvTP3R.', 'Activo', '2025-01-14 09:00:00', 1),
  (2, 'Sofia Aranda',           'sofia.aranda@correo.com',   '$2y$10$cCpJAIFxWlNy42VprlSmXO6oyu/cqzOfYVS8m5m7tAMS3ktvTP3R.', 'Activo', '2025-03-02 09:00:00', 1),
  (3, 'Karla Mendez',           'karla_m99@correo.com',      '$2y$10$cCpJAIFxWlNy42VprlSmXO6oyu/cqzOfYVS8m5m7tAMS3ktvTP3R.', 'Activo', '2026-09-01 12:30:00', NULL),
  (3, 'Ivan Torres',            'ivan.torres@correo.com',    '$2y$10$cCpJAIFxWlNy42VprlSmXO6oyu/cqzOfYVS8m5m7tAMS3ktvTP3R.', 'Activo', '2026-09-03 18:10:00', NULL),
  (3, 'Mariana Lozano',         'mariana.lozano@correo.com', '$2y$10$cCpJAIFxWlNy42VprlSmXO6oyu/cqzOfYVS8m5m7tAMS3ktvTP3R.', 'Activo', '2026-07-12 10:00:00', NULL);

INSERT INTO perfiles_adoptante
  (id_usuario, ocupacion, edad, tipo_vivienda, tiene_otras_mascotas, horas_solo_mascota, ha_tenido_mascotas, motivo_adopcion) VALUES
  (4, 'Estudiante',     22, 'Departamento', FALSE, 4, TRUE,  'Busco compañía para mi departamento y tengo experiencia previa con perros medianos.'),
  (5, 'Ingeniero',      28, 'Casa',         TRUE,  6, TRUE,  'Quiero darle un hogar a un gato rescatado.'),
  (6, 'Diseñadora',     31, 'Casa',         FALSE, 3, TRUE,  'Tengo patio amplio y tiempo para un perro grande.');

INSERT INTO mascotas (id_publicado_por, nombre, especie, raza, sexo, tamano, edad_meses, descripcion, foto, estado, fecha_publicacion) VALUES
  (2, 'Luna',  'Perro', 'Mestizo',  'Hembra', 'Mediano',  24, 'Cariñosa y activa, se lleva bien con niños.', 'uploads/mascotas/luna.jpg',  'Disponible', '2026-09-03 10:00:00'),
  (2, 'Michi', 'Gato',  'Mestizo',  'Macho',  'Pequeño',  12, 'Tranquilo y juguetón.',                       'uploads/mascotas/michi.jpg', 'En proceso', '2026-08-28 11:30:00'),
  (3, 'Rocky', 'Perro', 'Labrador', 'Macho',  'Grande',   48, 'Fiel y obediente.',                           'uploads/mascotas/rocky.jpg', 'Adoptada',   '2026-07-10 09:15:00');

INSERT INTO favoritos (id_usuario, id_mascota) VALUES
  (4, 1), (4, 2), (5, 2);

INSERT INTO solicitudes_adopcion
  (id_adoptante, id_mascota, id_revisor, fecha_solicitud, motivo, estado, notas_internas, fecha_resolucion) VALUES
  (4, 1, NULL, '2026-09-10 16:00:00', 'Busco compañía para mi departamento, tengo experiencia previa con perros medianos y tengo un parque cerca.', 'Pendiente', NULL, NULL),
  (5, 2, NULL, '2026-09-12 13:20:00', 'Quiero darle un hogar a Michi.', 'Pendiente', NULL, NULL),
  (6, 3, 3,    '2026-07-20 11:00:00', 'Tengo patio amplio y tiempo para un perro grande.', 'Aprobada', 'Se verificó el domicilio.', '2026-07-25 15:00:00');
