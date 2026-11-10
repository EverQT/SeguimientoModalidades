# BASE DE DATOS: TitulacionDB (DTIC - USFX)

---

## 📌 DESCRIPCIÓN E INSTRUCCIONES DE IMPORTACIÓN EN phpMyAdmin

La base de datos **`TitulacionDB`** ha sido diseñada para dar soporte integral al **Sistema de Seguimiento de Modalidades de Titulación** de la Universidad Mayor, Real y Pontificia de San Francisco Xavier de Chuquisaca (USFX).

### Características Técnicas del Esquema:
- **Gestor de Base de Datos compatible:** MySQL 5.7+ / MariaDB 10.2+ (optimizado para **phpMyAdmin**).
- **Charset / Collation:** `utf8mb4` / `utf8mb4_unicode_ci`.
- **Estructura total:** Contiene **62 tablas** (24 réplicas adaptadas del sistema académico SEA en `PascalCase` y 38 tablas especializadas creadas para el seguimiento DTIC).
- **Integridad y Reglas:** Incluye llaves foráneas completas, índices de rendimiento, restricciones `CHECK`, datos predeterminados en catálogos, 1 procedimiento almacenado (`SP_ValidarConflictoInteres`) y 7 triggers de negocio e inmutabilidad de bitácoras.

---

### 🚀 Pasos para importar este archivo en phpMyAdmin:

1. **Abrir phpMyAdmin** en tu navegador web.
2. Haz clic directamente en la pestaña **"Importar"** en la parte superior del panel central (no es necesario crear la base de datos previamente, el script se encarga de crearla mediante el comando `CREATE DATABASE IF NOT EXISTS TitulacionDB`).
3. En la sección **"Archivo a importar"**, haz clic en *Seleccionar archivo* o pega el contenido del script SQL en la pestaña **"SQL"**.
4. Asegúrate de que el juego de caracteres del archivo esté configurado en **`utf8`** o **`utf8mb4`**.
5. Haz clic en el botón **"Continuar"** o **"Ejecutar"** al final de la página.

---

## 🛠️ SCRIPT SQL COMPLETO

```sql
-- =====================================================================
--  SISTEMA DE SEGUIMIENTO DE MODALIDADES DE TITULACION  (DTIC - USFX)
--  Base de datos: TitulacionDB
--  Motor objetivo: MySQL 5.7+ / MariaDB 10.2+ (phpMyAdmin)
--
--  - 62 tablas: 24 basadas en el SEA (nombres de tablas y columnas en
--    PascalCase como en SQL Server) + 38 nuevas del sistema.
--  - Llaves foráneas completas, datos iniciales de catálogos,
--    1 procedimiento y 7 triggers de reglas de negocio.
--  - Para importar: phpMyAdmin > Importar > seleccionar este archivo.
--    (No hace falta crear la base antes: el script la crea.)
--
--  NOTA: en MySQL sobre Linux los nombres de tablas distinguen mayúsculas
--  y minúsculas. Usar siempre PascalCase exacto (ej. MateriasDocentes).
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS TitulacionDB
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;
USE TitulacionDB;

-- =====================================================================
--  PARTE 1 - TABLAS BASADAS EN EL SEA (réplica adaptada)
-- =====================================================================

-- ---------- Estructura institucional ----------
CREATE TABLE IF NOT EXISTS Facultades (
  CodigoFacultad      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  NombreFacultad      VARCHAR(150) NOT NULL,
  NombreCortoFacultad VARCHAR(40)  NULL,
  PRIMARY KEY (CodigoFacultad),
  UNIQUE KEY UQ_Facultades_Nombre (NombreFacultad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: Facultades';

CREATE TABLE IF NOT EXISTS Sedes (
  CodigoSede INT UNSIGNED NOT NULL AUTO_INCREMENT,
  NombreSede VARCHAR(100) NOT NULL,
  PRIMARY KEY (CodigoSede),
  UNIQUE KEY UQ_Sedes_Nombre (NombreSede)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: Sedes';

CREATE TABLE IF NOT EXISTS NivelesAcademicos (
  CodigoNivelAcademico INT UNSIGNED NOT NULL AUTO_INCREMENT,
  NombreNivelAcademico VARCHAR(80)  NOT NULL,
  Tipo                 VARCHAR(30)  NULL,
  PRIMARY KEY (CodigoNivelAcademico),
  UNIQUE KEY UQ_NivelesAcademicos_Nombre (NombreNivelAcademico)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: NivelesAcademicos (Técnico Superior, Licenciatura, etc.)';

CREATE TABLE IF NOT EXISTS EstadosCU (
  CodigoEstadoCU INT UNSIGNED NOT NULL AUTO_INCREMENT,
  NombreEstadoCU VARCHAR(60)  NOT NULL,
  PRIMARY KEY (CodigoEstadoCU),
  UNIQUE KEY UQ_EstadosCU_Nombre (NombreEstadoCU)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: EstadosCU';

CREATE TABLE IF NOT EXISTS EstadosCalificacion (
  CodigoEstadoCalificacion INT UNSIGNED NOT NULL AUTO_INCREMENT,
  NombreEstadoCalificacion VARCHAR(60)  NOT NULL,
  EsAprobado               TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = la materia cuenta como aprobada',
  PRIMARY KEY (CodigoEstadoCalificacion),
  UNIQUE KEY UQ_EstadosCalificacion_Nombre (NombreEstadoCalificacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: catálogo que en el SEA no aparece (qué código es aprobado)';

CREATE TABLE IF NOT EXISTS Carreras (
  CodigoCarrera        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  NombreCarrera        VARCHAR(150) NOT NULL,
  NombreCortoCarrera   VARCHAR(60)  NULL,
  SiglaCarrera         VARCHAR(10)  NULL,
  CodigoFacultad       INT UNSIGNED NOT NULL,
  CodigoNivelAcademico INT UNSIGNED NOT NULL,
  CodigoSede           INT UNSIGNED NOT NULL,
  NumeroPlanEstudiosDef INT UNSIGNED NULL COMMENT 'Plan vigente (sin FK para evitar referencia circular)',
  CodigoEstadoCarrera  TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 = activa',
  PRIMARY KEY (CodigoCarrera),
  KEY IX_Carreras_Facultad (CodigoFacultad),
  CONSTRAINT FK_Carreras_Facultades FOREIGN KEY (CodigoFacultad)       REFERENCES Facultades (CodigoFacultad),
  CONSTRAINT FK_Carreras_Niveles    FOREIGN KEY (CodigoNivelAcademico) REFERENCES NivelesAcademicos (CodigoNivelAcademico),
  CONSTRAINT FK_Carreras_Sedes      FOREIGN KEY (CodigoSede)           REFERENCES Sedes (CodigoSede)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: Carreras (simplificada)';

CREATE TABLE IF NOT EXISTS PlanesEstudios (
  CodigoCarrera       INT UNSIGNED NOT NULL,
  NumeroPlanEstudios  INT UNSIGNED NOT NULL,
  CodigoEstadoPlanEstudios TINYINT UNSIGNED NOT NULL DEFAULT 1,
  Resolucion          VARCHAR(60)  NULL,
  Annio               SMALLINT UNSIGNED NULL,
  TipoEgreso          VARCHAR(30)  NULL,
  PRIMARY KEY (CodigoCarrera, NumeroPlanEstudios),
  CONSTRAINT FK_PlanesEstudios_Carreras FOREIGN KEY (CodigoCarrera) REFERENCES Carreras (CodigoCarrera)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: PlanesEstudios';

CREATE TABLE IF NOT EXISTS MateriasPlanEstudios (
  CodigoCarrera          INT UNSIGNED NOT NULL,
  NumeroPlanEstudios     INT UNSIGNED NOT NULL,
  SiglaMateria           VARCHAR(10)  NOT NULL,
  NombreMateria          VARCHAR(150) NOT NULL,
  Curso                  TINYINT UNSIGNED NULL,
  CodigoTipoMateria      TINYINT UNSIGNED NULL,
  Creditos               DECIMAL(4,1) NULL,
  CodigoEstadoMateria    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  EsAsignaturaTitulacion TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'NUEVO: 1 = Seminario II, Proyecto I/II, Trabajo Dirigido, etc.',
  PRIMARY KEY (CodigoCarrera, NumeroPlanEstudios, SiglaMateria),
  CONSTRAINT FK_MateriasPlan_Planes FOREIGN KEY (CodigoCarrera, NumeroPlanEstudios)
    REFERENCES PlanesEstudios (CodigoCarrera, NumeroPlanEstudios)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: MateriasPlanEstudios (simplificada)';

-- ---------- Personas, usuarios y universitarios ----------
CREATE TABLE IF NOT EXISTS Personas (
  IdPersona         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  NumeroDocumento   VARCHAR(20)  NOT NULL COMMENT 'CI u otro documento de identidad',
  Paterno           VARCHAR(60)  NULL,
  Materno           VARCHAR(60)  NULL,
  Nombres           VARCHAR(100) NOT NULL,
  FechaNacimiento   DATE         NULL,
  Sexo              ENUM('M','F') NULL,
  Email             VARCHAR(150) NULL,
  Celular           VARCHAR(20)  NULL,
  Valido            TINYINT(1)   NOT NULL DEFAULT 1,
  FechaHoraRegistro DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (IdPersona),
  UNIQUE KEY UQ_Personas_Documento (NumeroDocumento),
  KEY IX_Personas_Nombre (Paterno, Materno, Nombres)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: Personas + PersonasDatosActualizables (solo correo y celular)';

CREATE TABLE IF NOT EXISTS Usuarios (
  CodigoUsuario     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdPersona         INT UNSIGNED NOT NULL,
  Login             VARCHAR(80)  NOT NULL COMMENT 'Usuario institucional. NO se guardan contraseñas aquí (RNF-01)',
  Activo            TINYINT(1)   NOT NULL DEFAULT 1,
  UltimoAcceso      DATETIME     NULL,
  FechaHoraRegistro DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (CodigoUsuario),
  UNIQUE KEY UQ_Usuarios_Login (Login),
  KEY IX_Usuarios_Persona (IdPersona),
  CONSTRAINT FK_Usuarios_Personas FOREIGN KEY (IdPersona) REFERENCES Personas (IdPersona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: Usuarios (sin Contrasenia ni Llave)';

CREATE TABLE IF NOT EXISTS Universitarios (
  CU                    VARCHAR(15)  NOT NULL,
  IdPersona             INT UNSIGNED NOT NULL,
  CodigoCarrera         INT UNSIGNED NOT NULL,
  NumeroPlanEstudiosAct INT UNSIGNED NOT NULL,
  GestionIngreso        SMALLINT UNSIGNED NULL,
  CodigoEstadoCU        INT UNSIGNED NOT NULL,
  CodigoSede            INT UNSIGNED NOT NULL,
  FechaHoraRegistro     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (CU),
  KEY IX_Universitarios_Persona (IdPersona),
  KEY IX_Universitarios_Carrera (CodigoCarrera, NumeroPlanEstudiosAct),
  CONSTRAINT FK_Universitarios_Personas FOREIGN KEY (IdPersona)     REFERENCES Personas (IdPersona),
  CONSTRAINT FK_Universitarios_Planes   FOREIGN KEY (CodigoCarrera, NumeroPlanEstudiosAct)
    REFERENCES PlanesEstudios (CodigoCarrera, NumeroPlanEstudios),
  CONSTRAINT FK_Universitarios_Estados  FOREIGN KEY (CodigoEstadoCU) REFERENCES EstadosCU (CodigoEstadoCU),
  CONSTRAINT FK_Universitarios_Sedes    FOREIGN KEY (CodigoSede)     REFERENCES Sedes (CodigoSede)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: Universitarios (CU = código único del estudiante)';

-- ---------- Académico: notas, egreso, docentes ----------
CREATE TABLE IF NOT EXISTS Calificaciones (
  CU                       VARCHAR(15)  NOT NULL,
  GestionAcademica         VARCHAR(10)  NOT NULL,
  CodigoModalidadCurso     INT UNSIGNED NOT NULL COMMENT 'Modalidad del curso en el SEA (semestral, verano...)',
  SiglaMateria             VARCHAR(10)  NOT NULL,
  CodigoCarrera            INT UNSIGNED NOT NULL,
  NumeroPlanEstudios       INT UNSIGNED NOT NULL,
  NotaFinal                DECIMAL(5,2) NULL,
  NotaFinalSegunda         DECIMAL(5,2) NULL COMMENT 'Si tiene valor, la materia no se aprobó en primera instancia (Excelencia)',
  CodigoEstadoCalificacion INT UNSIGNED NOT NULL,
  Observaciones            VARCHAR(255) NULL,
  FechaHoraRegistro        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (CU, GestionAcademica, CodigoModalidadCurso, SiglaMateria),
  KEY IX_Calificaciones_Materia (CodigoCarrera, NumeroPlanEstudios, SiglaMateria),
  CONSTRAINT FK_Calificaciones_Universitarios FOREIGN KEY (CU) REFERENCES Universitarios (CU),
  CONSTRAINT FK_Calificaciones_Materias FOREIGN KEY (CodigoCarrera, NumeroPlanEstudios, SiglaMateria)
    REFERENCES MateriasPlanEstudios (CodigoCarrera, NumeroPlanEstudios, SiglaMateria),
  CONSTRAINT FK_Calificaciones_Estados FOREIGN KEY (CodigoEstadoCalificacion)
    REFERENCES EstadosCalificacion (CodigoEstadoCalificacion),
  CONSTRAINT CK_Calificaciones_Nota  CHECK (NotaFinal IS NULL OR (NotaFinal BETWEEN 0 AND 100)),
  CONSTRAINT CK_Calificaciones_Nota2 CHECK (NotaFinalSegunda IS NULL OR (NotaFinalSegunda BETWEEN 0 AND 100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: Calificaciones (solo notas finales, sin parciales)';

CREATE TABLE IF NOT EXISTS Egresados (
  CU                 VARCHAR(15)  NOT NULL,
  CodigoCarrera      INT UNSIGNED NOT NULL,
  NumeroPlanEstudios INT UNSIGNED NOT NULL,
  GestionAcademica   VARCHAR(10)  NOT NULL,
  FechaHoraRegistro  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (CU),
  KEY IX_Egresados_Plan (CodigoCarrera, NumeroPlanEstudios),
  CONSTRAINT FK_Egresados_Universitarios FOREIGN KEY (CU) REFERENCES Universitarios (CU),
  CONSTRAINT FK_Egresados_Planes FOREIGN KEY (CodigoCarrera, NumeroPlanEstudios)
    REFERENCES PlanesEstudios (CodigoCarrera, NumeroPlanEstudios)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: egresados (constancia de conclusión de estudios, Fase I)';

CREATE TABLE IF NOT EXISTS MateriasDocentes (
  GestionAcademica     VARCHAR(10)  NOT NULL,
  CodigoModalidadCurso INT UNSIGNED NOT NULL,
  CodigoCarrera        INT UNSIGNED NOT NULL,
  NumeroPlanEstudios   INT UNSIGNED NOT NULL,
  SiglaMateria         VARCHAR(10)  NOT NULL,
  Grupo                VARCHAR(5)   NOT NULL,
  IdPersona            INT UNSIGNED NOT NULL COMMENT 'Docente de la materia y grupo',
  NumeroEstudiantesLimite SMALLINT UNSIGNED NULL,
  FechaHoraRegistro    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (GestionAcademica, CodigoModalidadCurso, CodigoCarrera, NumeroPlanEstudios, SiglaMateria, Grupo),
  KEY IX_MateriasDocentes_Docente (IdPersona),
  CONSTRAINT FK_MateriasDocentes_Personas FOREIGN KEY (IdPersona) REFERENCES Personas (IdPersona),
  CONSTRAINT FK_MateriasDocentes_Materias FOREIGN KEY (CodigoCarrera, NumeroPlanEstudios, SiglaMateria)
    REFERENCES MateriasPlanEstudios (CodigoCarrera, NumeroPlanEstudios, SiglaMateria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: MateriasDocentes (docente por materia y grupo)';

CREATE TABLE IF NOT EXISTS DetallesProgramaciones (
  CU                   VARCHAR(15)  NOT NULL,
  GestionAcademica     VARCHAR(10)  NOT NULL,
  CodigoModalidadCurso INT UNSIGNED NOT NULL,
  SiglaMateria         VARCHAR(10)  NOT NULL,
  CodigoCarrera        INT UNSIGNED NOT NULL,
  NumeroPlanEstudios   INT UNSIGNED NOT NULL,
  Grupo                VARCHAR(5)   NOT NULL,
  PRIMARY KEY (CU, GestionAcademica, CodigoModalidadCurso, SiglaMateria),
  KEY IX_DetallesProg_Grupo (GestionAcademica, CodigoModalidadCurso, CodigoCarrera, NumeroPlanEstudios, SiglaMateria, Grupo),
  CONSTRAINT FK_DetallesProg_Universitarios FOREIGN KEY (CU) REFERENCES Universitarios (CU),
  CONSTRAINT FK_DetallesProg_MateriasDocentes FOREIGN KEY
    (GestionAcademica, CodigoModalidadCurso, CodigoCarrera, NumeroPlanEstudios, SiglaMateria, Grupo)
    REFERENCES MateriasDocentes
    (GestionAcademica, CodigoModalidadCurso, CodigoCarrera, NumeroPlanEstudios, SiglaMateria, Grupo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: DetallesProgramaciones (qué estudiante cursa qué grupo)';

CREATE TABLE IF NOT EXISTS Autoridades (
  IdAutoridad    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdPersona      INT UNSIGNED NOT NULL,
  Ambito         ENUM('CARRERA','FACULTAD','UNIVERSIDAD') NOT NULL,
  Cargo          VARCHAR(80)  NOT NULL COMMENT 'Director de Carrera, Secretaria de Carrera, Decano, Rector, etc.',
  CodigoCarrera  INT UNSIGNED NULL,
  CodigoFacultad INT UNSIGNED NULL,
  CodigoSede     INT UNSIGNED NULL,
  Interino       TINYINT(1)   NOT NULL DEFAULT 0,
  FechaInicio    DATE         NOT NULL,
  FechaFin       DATE         NULL,
  Activo         TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (IdAutoridad),
  KEY IX_Autoridades_Persona (IdPersona),
  KEY IX_Autoridades_Unidad (Ambito, CodigoCarrera, CodigoFacultad),
  CONSTRAINT FK_Autoridades_Personas   FOREIGN KEY (IdPersona)      REFERENCES Personas (IdPersona),
  CONSTRAINT FK_Autoridades_Carreras   FOREIGN KEY (CodigoCarrera)  REFERENCES Carreras (CodigoCarrera),
  CONSTRAINT FK_Autoridades_Facultades FOREIGN KEY (CodigoFacultad) REFERENCES Facultades (CodigoFacultad),
  CONSTRAINT FK_Autoridades_Sedes      FOREIGN KEY (CodigoSede)     REFERENCES Sedes (CodigoSede)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: PERSONASAUTORIDADESCARRERAS + FACULTAD + UNIVERSIDAD + Autoridades (unificadas)';

-- =====================================================================
--  PARTE 2 - CATÁLOGOS Y CONFIGURACIÓN (RF-01, RF-02, RNF-13)
-- =====================================================================

CREATE TABLE IF NOT EXISTS ModalidadesTitulacion (
  CodigoModalidadTitulacion INT UNSIGNED NOT NULL AUTO_INCREMENT,
  NombreModalidad           VARCHAR(100) NOT NULL,
  Sigla                     VARCHAR(20)  NOT NULL,
  Categoria                 ENUM('INVESTIGACION','PRACTICA_LABORAL','EVALUACIONES_ACADEMICAS','RECONOCIMIENTO_DESEMPENO') NOT NULL,
  CodigoModalidadCurso      INT UNSIGNED NOT NULL COMMENT 'Código equivalente en el SEA (CodigoModalidadCurso)',
  Activa                    TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (CodigoModalidadTitulacion),
  UNIQUE KEY UQ_ModalidadesTitulacion_Nombre (NombreModalidad),
  UNIQUE KEY UQ_ModalidadesTitulacion_Sigla (Sigla),
  UNIQUE KEY UQ_ModalidadesTitulacion_CodigoSEA (CodigoModalidadCurso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: las 9 modalidades y su categoría';

CREATE TABLE IF NOT EXISTS FasesTramite (
  CodigoFase TINYINT UNSIGNED NOT NULL,
  NombreFase VARCHAR(120) NOT NULL,
  Orden      TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (CodigoFase),
  UNIQUE KEY UQ_FasesTramite_Orden (Orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: las 5 fases macro (barra de progreso, RNF-10)';

CREATE TABLE IF NOT EXISTS Roles (
  CodigoRol   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  NombreRol   VARCHAR(80)  NOT NULL,
  Descripcion VARCHAR(255) NULL,
  Activo      TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (CodigoRol),
  UNIQUE KEY UQ_Roles_Nombre (NombreRol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: los 12 paneles + la vista de autoridades centrales';

CREATE TABLE IF NOT EXISTS EstadosTramite (
  CodigoEstadoTramite       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CodigoFase                TINYINT UNSIGNED NULL COMMENT 'NULL = estado transversal (congelado, abandono, etc.)',
  CodigoModalidadTitulacion INT UNSIGNED NULL COMMENT 'NULL = estado canónico para todas las modalidades',
  NombreCanonico            VARCHAR(40)  NOT NULL,
  Etiqueta                  VARCHAR(100) NOT NULL COMMENT 'Texto que ve el usuario (puede variar por modalidad)',
  EsFinal                   TINYINT(1)   NOT NULL DEFAULT 0,
  Orden                     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (CodigoEstadoTramite),
  UNIQUE KEY UQ_EstadosTramite (CodigoModalidadTitulacion, NombreCanonico),
  KEY IX_EstadosTramite_Fase (CodigoFase),
  CONSTRAINT FK_EstadosTramite_Fases FOREIGN KEY (CodigoFase) REFERENCES FasesTramite (CodigoFase),
  CONSTRAINT FK_EstadosTramite_Modalidades FOREIGN KEY (CodigoModalidadTitulacion)
    REFERENCES ModalidadesTitulacion (CodigoModalidadTitulacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: estado canónico por fase con etiqueta por modalidad';

CREATE TABLE IF NOT EXISTS TransicionesEstado (
  IdTransicion         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CodigoEstadoOrigen   INT UNSIGNED NOT NULL,
  CodigoEstadoDestino  INT UNSIGNED NOT NULL,
  CodigoRol            INT UNSIGNED NOT NULL COMMENT 'Rol que puede ejecutar el cambio',
  RequiereResolucion   TINYINT(1)   NOT NULL DEFAULT 0,
  Descripcion          VARCHAR(255) NULL,
  PRIMARY KEY (IdTransicion),
  UNIQUE KEY UQ_Transiciones (CodigoEstadoOrigen, CodigoEstadoDestino, CodigoRol),
  CONSTRAINT FK_Transiciones_Origen  FOREIGN KEY (CodigoEstadoOrigen)  REFERENCES EstadosTramite (CodigoEstadoTramite),
  CONSTRAINT FK_Transiciones_Destino FOREIGN KEY (CodigoEstadoDestino) REFERENCES EstadosTramite (CodigoEstadoTramite),
  CONSTRAINT FK_Transiciones_Roles   FOREIGN KEY (CodigoRol)           REFERENCES Roles (CodigoRol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: reglas de avance entre estados (RF-01)';

CREATE TABLE IF NOT EXISTS PlanesEstudiosTitulaciones (
  CodigoCarrera             INT UNSIGNED NOT NULL,
  NumeroPlanEstudios        INT UNSIGNED NOT NULL,
  CodigoNivelAcademico      INT UNSIGNED NOT NULL,
  CodigoModalidadTitulacion INT UNSIGNED NOT NULL COMMENT 'En el SEA: CodigoFormaTitulacion',
  Estado                    TINYINT UNSIGNED NOT NULL DEFAULT 1,
  Reglamentacion            VARCHAR(255) NULL,
  PRIMARY KEY (CodigoCarrera, NumeroPlanEstudios, CodigoNivelAcademico, CodigoModalidadTitulacion),
  CONSTRAINT FK_PlanesTit_Planes FOREIGN KEY (CodigoCarrera, NumeroPlanEstudios)
    REFERENCES PlanesEstudios (CodigoCarrera, NumeroPlanEstudios),
  CONSTRAINT FK_PlanesTit_Niveles FOREIGN KEY (CodigoNivelAcademico) REFERENCES NivelesAcademicos (CodigoNivelAcademico),
  CONSTRAINT FK_PlanesTit_Modalidades FOREIGN KEY (CodigoModalidadTitulacion)
    REFERENCES ModalidadesTitulacion (CodigoModalidadTitulacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: PlanesEstudiosTitulaciones (qué modalidades ofrece cada plan)';

CREATE TABLE IF NOT EXISTS ConfigModalidad (
  IdConfig                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CodigoModalidadTitulacion INT UNSIGNED NOT NULL,
  CodigoFacultad            INT UNSIGNED NULL COMMENT 'NULL = aplica a todas',
  CodigoCarrera             INT UNSIGNED NULL COMMENT 'NULL = aplica a toda la facultad',
  GestionDesde              SMALLINT UNSIGNED NOT NULL,
  GestionHasta              SMALLINT UNSIGNED NULL,
  NotaMinimaAprobacion      DECIMAL(5,2) NOT NULL DEFAULT 51.00,
  NotaMaxima                DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  NumeroDefensas            TINYINT UNSIGNED NOT NULL DEFAULT 1,
  NumeroRotaciones          TINYINT UNSIGNED NULL,
  PorcentajeEvalExterna     DECIMAL(5,2) NULL COMMENT 'Hasta 30% en prácticas',
  PorcentajeAsistenciaMin   DECIMAL(5,2) NULL COMMENT '70% en Trabajo Dirigido / Pasantía',
  PromedioMinExcelencia     DECIMAL(5,2) NULL COMMENT '70 o 75 según facultad',
  PlazoTribunalDias         SMALLINT UNSIGNED NULL,
  PlazoSubsanacionDias      SMALLINT UNSIGNED NULL,
  PlazoMaximoMeses          SMALLINT UNSIGNED NULL,
  DiasGracia                SMALLINT UNSIGNED NULL,
  DiasHabiles               TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1 = días hábiles, 0 = calendario',
  ReglaConsolidacionTribunal ENUM('UNANIMIDAD','MAYORIA') NOT NULL DEFAULT 'UNANIMIDAD',
  RequiereCertificadoNoDuplicidad TINYINT(1) NOT NULL DEFAULT 1,
  ParametrosExtra           JSON NULL COMMENT 'Reglas adicionales sin cambiar el esquema',
  Activo                    TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (IdConfig),
  UNIQUE KEY UQ_ConfigModalidad (CodigoModalidadTitulacion, CodigoFacultad, CodigoCarrera, GestionDesde),
  CONSTRAINT FK_Config_Modalidades FOREIGN KEY (CodigoModalidadTitulacion) REFERENCES ModalidadesTitulacion (CodigoModalidadTitulacion),
  CONSTRAINT FK_Config_Facultades  FOREIGN KEY (CodigoFacultad) REFERENCES Facultades (CodigoFacultad),
  CONSTRAINT FK_Config_Carreras    FOREIGN KEY (CodigoCarrera)  REFERENCES Carreras (CodigoCarrera)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: parametrización por facultad, carrera y modalidad (RF-01, RNF-13)';

CREATE TABLE IF NOT EXISTS TiposDocumento (
  CodigoTipoDocumento  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  NombreTipoDocumento  VARCHAR(120) NOT NULL,
  Descripcion          VARCHAR(255) NULL,
  ExtensionesPermitidas VARCHAR(40) NOT NULL DEFAULT 'pdf',
  TamanoMaximoMB       SMALLINT UNSIGNED NOT NULL DEFAULT 20,
  GeneradoPorSistema   TINYINT(1)   NOT NULL DEFAULT 0,
  Activo               TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (CodigoTipoDocumento),
  UNIQUE KEY UQ_TiposDocumento_Nombre (NombreTipoDocumento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: catálogo de documentos del trámite (RNF-14: PDF no protegido)';

CREATE TABLE IF NOT EXISTS RequisitosDocumento (
  IdRequisito               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CodigoModalidadTitulacion INT UNSIGNED NOT NULL,
  CodigoFase                TINYINT UNSIGNED NOT NULL,
  CodigoTipoDocumento       INT UNSIGNED NOT NULL,
  CodigoCarrera             INT UNSIGNED NULL COMMENT 'NULL = todas las carreras',
  EsObligatorio             TINYINT(1)   NOT NULL DEFAULT 1,
  Orden                     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  Activo                    TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (IdRequisito),
  KEY IX_Requisitos_Modalidad (CodigoModalidadTitulacion, CodigoFase),
  CONSTRAINT FK_Requisitos_Modalidades FOREIGN KEY (CodigoModalidadTitulacion) REFERENCES ModalidadesTitulacion (CodigoModalidadTitulacion),
  CONSTRAINT FK_Requisitos_Fases   FOREIGN KEY (CodigoFase) REFERENCES FasesTramite (CodigoFase),
  CONSTRAINT FK_Requisitos_Tipos   FOREIGN KEY (CodigoTipoDocumento) REFERENCES TiposDocumento (CodigoTipoDocumento),
  CONSTRAINT FK_Requisitos_Carreras FOREIGN KEY (CodigoCarrera) REFERENCES Carreras (CodigoCarrera)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: documentos exigidos por modalidad, fase y carrera (RF-04)';

CREATE TABLE IF NOT EXISTS CalendarioInstitucional (
  IdCalendario   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  FechaInicio    DATE         NOT NULL,
  FechaFin       DATE         NOT NULL,
  Descripcion    VARCHAR(150) NOT NULL,
  Tipo           ENUM('FERIADO','RECESO','SUSPENSION') NOT NULL DEFAULT 'FERIADO',
  CodigoFacultad INT UNSIGNED NULL COMMENT 'NULL = toda la universidad',
  PausaPlazos    TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (IdCalendario),
  KEY IX_Calendario_Fechas (FechaInicio, FechaFin),
  CONSTRAINT FK_Calendario_Facultades FOREIGN KEY (CodigoFacultad) REFERENCES Facultades (CodigoFacultad),
  CONSTRAINT CK_Calendario_Fechas CHECK (FechaFin >= FechaInicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: feriados y recesos que pausan plazos (RF-13)';

CREATE TABLE IF NOT EXISTS Plantillas (
  IdPlantilla    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CodigoPlantilla VARCHAR(40) NOT NULL,
  NombrePlantilla VARCHAR(120) NOT NULL,
  Tipo           ENUM('PDF','NOTIFICACION') NOT NULL,
  Asunto         VARCHAR(200) NULL,
  Contenido      MEDIUMTEXT   NOT NULL,
  Version        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  CodigoFacultad INT UNSIGNED NULL,
  Activa         TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (IdPlantilla),
  UNIQUE KEY UQ_Plantillas (CodigoPlantilla, Version),
  CONSTRAINT FK_Plantillas_Facultades FOREIGN KEY (CodigoFacultad) REFERENCES Facultades (CodigoFacultad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: plantillas de PDF (auto decanal, planillas) y de mensajes (RF-12, RF-14)';

CREATE TABLE IF NOT EXISTS UsuariosRolesAlcance (
  IdAsignacion   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CodigoUsuario  INT UNSIGNED NOT NULL,
  CodigoRol      INT UNSIGNED NOT NULL,
  CodigoFacultad INT UNSIGNED NULL,
  CodigoCarrera  INT UNSIGNED NULL,
  FechaInicio    DATE         NOT NULL,
  FechaFin       DATE         NULL,
  Activo         TINYINT(1)   NOT NULL DEFAULT 1,
  AsignadoPor    INT UNSIGNED NULL,
  PRIMARY KEY (IdAsignacion),
  KEY IX_URA_Usuario (CodigoUsuario, Activo),
  CONSTRAINT FK_URA_Usuarios    FOREIGN KEY (CodigoUsuario)  REFERENCES Usuarios (CodigoUsuario),
  CONSTRAINT FK_URA_Roles       FOREIGN KEY (CodigoRol)      REFERENCES Roles (CodigoRol),
  CONSTRAINT FK_URA_Facultades  FOREIGN KEY (CodigoFacultad) REFERENCES Facultades (CodigoFacultad),
  CONSTRAINT FK_URA_Carreras    FOREIGN KEY (CodigoCarrera)  REFERENCES Carreras (CodigoCarrera),
  CONSTRAINT FK_URA_AsignadoPor FOREIGN KEY (AsignadoPor)    REFERENCES Usuarios (CodigoUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: RBAC por rol, unidad y vigencia; un usuario puede tener varios roles (RF-02)';

CREATE TABLE IF NOT EXISTS PermisosRolEstado (
  IdPermiso           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CodigoRol           INT UNSIGNED NOT NULL,
  CodigoEstadoTramite INT UNSIGNED NOT NULL,
  Accion              VARCHAR(40)  NOT NULL COMMENT 'VER, CARGAR, VALIDAR, DESIGNAR, REGISTRAR, etc.',
  Permitido           TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (IdPermiso),
  UNIQUE KEY UQ_PermisosRolEstado (CodigoRol, CodigoEstadoTramite, Accion),
  CONSTRAINT FK_Permisos_Roles   FOREIGN KEY (CodigoRol) REFERENCES Roles (CodigoRol),
  CONSTRAINT FK_Permisos_Estados FOREIGN KEY (CodigoEstadoTramite) REFERENCES EstadosTramite (CodigoEstadoTramite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: qué botones ve cada rol en cada estado (RF-02)';

CREATE TABLE IF NOT EXISTS LineasInvestigacion (
  IdLinea       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CodigoCarrera INT UNSIGNED NOT NULL,
  NombreLinea   VARCHAR(200) NOT NULL,
  Activa        TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (IdLinea),
  UNIQUE KEY UQ_Lineas (CodigoCarrera, NombreLinea),
  CONSTRAINT FK_Lineas_Carreras FOREIGN KEY (CodigoCarrera) REFERENCES Carreras (CodigoCarrera)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: líneas de investigación por carrera (pertinencia del tema)';

CREATE TABLE IF NOT EXISTS ParametrosSistema (
  Clave                    VARCHAR(80)  NOT NULL,
  Valor                    VARCHAR(500) NOT NULL,
  Descripcion              VARCHAR(255) NULL,
  FechaHoraModificacion    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CodigoUsuarioModificacion INT UNSIGNED NULL,
  PRIMARY KEY (Clave),
  CONSTRAINT FK_Parametros_Usuarios FOREIGN KEY (CodigoUsuarioModificacion) REFERENCES Usuarios (CodigoUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: parámetros generales del sistema (Administrador DTIC)';

-- =====================================================================
--  PARTE 3 - NÚCLEO DEL TRÁMITE (Fases I a V)
-- =====================================================================

CREATE TABLE IF NOT EXISTS Expedientes (
  IdExpediente              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CU                        VARCHAR(15)  NOT NULL,
  CodigoCarrera             INT UNSIGNED NOT NULL,
  NumeroPlanEstudios        INT UNSIGNED NOT NULL,
  CodigoModalidadTitulacion INT UNSIGNED NOT NULL,
  GestionAcademica          VARCHAR(10)  NOT NULL,
  CodigoFase                TINYINT UNSIGNED NOT NULL,
  CodigoEstadoTramite       INT UNSIGNED NOT NULL,
  Semaforo                  ENUM('VERDE','AMARILLO','ROJO','AZUL','GRIS') NOT NULL DEFAULT 'VERDE',
  FechaInicio               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FechaConclusion           DATETIME     NULL,
  IdExpedienteAnterior      INT UNSIGNED NULL COMMENT 'Expediente archivado por cambio de modalidad (RF-18)',
  Archivado                 TINYINT(1)   NOT NULL DEFAULT 0,
  Observaciones             VARCHAR(500) NULL,
  CodigoUsuarioRegistro     INT UNSIGNED NOT NULL,
  FechaHoraRegistro         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FechaHoraActualizacion    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (IdExpediente),
  KEY IX_Expedientes_CU (CU),
  KEY IX_Expedientes_Carrera (CodigoCarrera, CodigoModalidadTitulacion, CodigoEstadoTramite),
  KEY IX_Expedientes_Semaforo (Semaforo, CodigoFase),
  CONSTRAINT FK_Expedientes_Universitarios FOREIGN KEY (CU) REFERENCES Universitarios (CU),
  CONSTRAINT FK_Expedientes_Planes FOREIGN KEY (CodigoCarrera, NumeroPlanEstudios)
    REFERENCES PlanesEstudios (CodigoCarrera, NumeroPlanEstudios),
  CONSTRAINT FK_Expedientes_Modalidades FOREIGN KEY (CodigoModalidadTitulacion)
    REFERENCES ModalidadesTitulacion (CodigoModalidadTitulacion),
  CONSTRAINT FK_Expedientes_Fases   FOREIGN KEY (CodigoFase) REFERENCES FasesTramite (CodigoFase),
  CONSTRAINT FK_Expedientes_Estados FOREIGN KEY (CodigoEstadoTramite) REFERENCES EstadosTramite (CodigoEstadoTramite),
  CONSTRAINT FK_Expedientes_Anterior FOREIGN KEY (IdExpedienteAnterior) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_Expedientes_Usuarios FOREIGN KEY (CodigoUsuarioRegistro) REFERENCES Usuarios (CodigoUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: registro central del trámite de cada estudiante';

CREATE TABLE IF NOT EXISTS ExpedientesEstadosHistorial (
  IdHistorial           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente          INT UNSIGNED NOT NULL,
  CodigoEstadoAnterior  INT UNSIGNED NULL,
  CodigoEstadoNuevo     INT UNSIGNED NOT NULL,
  FechaHoraCambio       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CodigoUsuario         INT UNSIGNED NULL COMMENT 'NULL = cambio automático del sistema',
  Motivo                VARCHAR(255) NULL,
  PRIMARY KEY (IdHistorial),
  KEY IX_Historial_Expediente (IdExpediente, FechaHoraCambio),
  CONSTRAINT FK_Historial_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_Historial_Anterior FOREIGN KEY (CodigoEstadoAnterior) REFERENCES EstadosTramite (CodigoEstadoTramite),
  CONSTRAINT FK_Historial_Nuevo    FOREIGN KEY (CodigoEstadoNuevo)    REFERENCES EstadosTramite (CodigoEstadoTramite),
  CONSTRAINT FK_Historial_Usuarios FOREIGN KEY (CodigoUsuario) REFERENCES Usuarios (CodigoUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: cada cambio de estado (RF-22: tiempos promedio y trazabilidad)';

CREATE TABLE IF NOT EXISTS VerificacionesAcademicas (
  IdVerificacion        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente          INT UNSIGNED NOT NULL,
  Tipo                  ENUM('EGRESO','KARDEX','PROMEDIO','PRIMERA_INSTANCIA','MATERIAS_HABILITANTES') NOT NULL,
  Resultado             ENUM('CUMPLE','NO_CUMPLE','PENDIENTE') NOT NULL DEFAULT 'PENDIENTE',
  ValorCalculado        DECIMAL(6,2) NULL COMMENT 'Ej.: promedio acumulado',
  Detalle               TEXT NULL,
  FuenteDatos           VARCHAR(30)  NOT NULL DEFAULT 'SEA',
  FechaHoraVerificacion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CodigoUsuario         INT UNSIGNED NULL COMMENT 'NULL = verificación automática',
  PRIMARY KEY (IdVerificacion),
  KEY IX_Verificaciones_Expediente (IdExpediente),
  CONSTRAINT FK_Verificaciones_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_Verificaciones_Usuarios FOREIGN KEY (CodigoUsuario) REFERENCES Usuarios (CodigoUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: resultado de auditar egreso, kardex y promedio en Fase I (RF-03)';

CREATE TABLE IF NOT EXISTS AutorizacionesExcepciones (
  IdAutorizacion     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente       INT UNSIGNED NOT NULL,
  Tipo               ENUM('PRORROGA','REACTIVACION','CAMBIO_MODALIDAD','OMISION_NO_DUPLICIDAD','CORRECCION_NOTA','HABILITACION_ESPECIAL','ABANDONO') NOT NULL,
  Motivo             TEXT NOT NULL,
  Estado             ENUM('SOLICITADA','APROBADA','RECHAZADA') NOT NULL DEFAULT 'SOLICITADA',
  SolicitadoPor      INT UNSIGNED NOT NULL,
  FechaHoraSolicitud DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  AutorizadoPor      INT UNSIGNED NULL,
  FechaHoraResolucion DATETIME    NULL,
  NroResolucion      VARCHAR(40)  NULL,
  FechaNueva         DATE         NULL COMMENT 'Nueva fecha límite (prórroga)',
  CodigoModalidadDestino INT UNSIGNED NULL COMMENT 'Cambio de modalidad',
  Observaciones      VARCHAR(500) NULL,
  PRIMARY KEY (IdAutorizacion),
  KEY IX_AutExc_Expediente (IdExpediente, Tipo, Estado),
  CONSTRAINT FK_AutExc_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_AutExc_Solicitante FOREIGN KEY (SolicitadoPor) REFERENCES Usuarios (CodigoUsuario),
  CONSTRAINT FK_AutExc_Autorizador FOREIGN KEY (AutorizadoPor) REFERENCES Usuarios (CodigoUsuario),
  CONSTRAINT FK_AutExc_ModalidadDestino FOREIGN KEY (CodigoModalidadDestino)
    REFERENCES ModalidadesTitulacion (CodigoModalidadTitulacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: prórrogas, reactivaciones, cambios de modalidad, correcciones de nota (RF-05, 13, 15, 18, 20)';

CREATE TABLE IF NOT EXISTS DocumentosExpediente (
  IdDocumento            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente           INT UNSIGNED NOT NULL,
  CodigoTipoDocumento    INT UNSIGNED NOT NULL,
  CodigoFase             TINYINT UNSIGNED NULL,
  Version                SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  NombreArchivo          VARCHAR(255) NOT NULL,
  RutaArchivo            VARCHAR(500) NOT NULL,
  TamanoBytes            BIGINT UNSIGNED NULL,
  HashSHA256             CHAR(64)     NOT NULL COMMENT 'Integridad del archivo (RNF-03)',
  Estado                 ENUM('CARGADO','VALIDADO','OBSERVADO','RECHAZADO','REEMPLAZADO') NOT NULL DEFAULT 'CARGADO',
  EsDefinitivo           TINYINT(1)   NOT NULL DEFAULT 0,
  IdDocumentoReemplazado INT UNSIGNED NULL,
  CodigoUsuarioCarga     INT UNSIGNED NOT NULL,
  FechaHoraCarga         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CodigoUsuarioValida    INT UNSIGNED NULL,
  FechaHoraValidacion    DATETIME     NULL,
  ObservacionValidacion  VARCHAR(500) NULL,
  PRIMARY KEY (IdDocumento),
  KEY IX_Documentos_Expediente (IdExpediente, CodigoTipoDocumento, Version),
  KEY IX_Documentos_Hash (HashSHA256),
  CONSTRAINT FK_Documentos_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_Documentos_Tipos  FOREIGN KEY (CodigoTipoDocumento) REFERENCES TiposDocumento (CodigoTipoDocumento),
  CONSTRAINT FK_Documentos_Fases  FOREIGN KEY (CodigoFase) REFERENCES FasesTramite (CodigoFase),
  CONSTRAINT FK_Documentos_Reemplazado FOREIGN KEY (IdDocumentoReemplazado) REFERENCES DocumentosExpediente (IdDocumento),
  CONSTRAINT FK_Documentos_UsuarioCarga FOREIGN KEY (CodigoUsuarioCarga) REFERENCES Usuarios (CodigoUsuario),
  CONSTRAINT FK_Documentos_UsuarioValida FOREIGN KEY (CodigoUsuarioValida) REFERENCES Usuarios (CodigoUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: archivos del expediente con versión, hash y validación (RF-04)';

-- ---------- Fase II: tema, perfil, tutor y tribunal ----------
CREATE TABLE IF NOT EXISTS TemasPerfil (
  IdTema                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente          INT UNSIGNED NOT NULL,
  Titulo                VARCHAR(400) NOT NULL,
  IdLinea               INT UNSIGNED NULL,
  PalabrasClave         VARCHAR(250) NULL,
  Resumen               TEXT NULL,
  Version               SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  FechaPresentacion     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Estado                ENUM('PRESENTADO','EN_REVISION','OBSERVADO','APROBADO','RECHAZADO','VENCIDO') NOT NULL DEFAULT 'PRESENTADO',
  VigenteHasta          DATE         NULL,
  NumeroCertificadoNoDuplicidad VARCHAR(40) NULL,
  FechaCertificadoNoDuplicidad  DATE NULL,
  IdDocumentoCertificado INT UNSIGNED NULL,
  PRIMARY KEY (IdTema),
  KEY IX_Temas_Expediente (IdExpediente),
  FULLTEXT KEY FT_Temas_Titulo (Titulo, PalabrasClave),
  CONSTRAINT FK_Temas_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_Temas_Lineas FOREIGN KEY (IdLinea) REFERENCES LineasInvestigacion (IdLinea),
  CONSTRAINT FK_Temas_Certificado FOREIGN KEY (IdDocumentoCertificado) REFERENCES DocumentosExpediente (IdDocumento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: tema y perfil con certificado de no duplicidad (RF-05, RF-21)';

CREATE TABLE IF NOT EXISTS RevisionesPerfil (
  IdRevision       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdTema           INT UNSIGNED NOT NULL,
  Instancia        ENUM('DOCENTE_ASIGNATURA','UNIDAD_INVESTIGACION','DIRECTOR_CARRERA') NOT NULL,
  IdPersonaRevisor INT UNSIGNED NOT NULL,
  Resultado        ENUM('AUTORIZADO','OBSERVADO','RECHAZADO') NOT NULL,
  Observaciones    VARCHAR(1000) NULL,
  FechaHora        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (IdRevision),
  KEY IX_RevPerfil_Tema (IdTema),
  CONSTRAINT FK_RevPerfil_Temas FOREIGN KEY (IdTema) REFERENCES TemasPerfil (IdTema),
  CONSTRAINT FK_RevPerfil_Personas FOREIGN KEY (IdPersonaRevisor) REFERENCES Personas (IdPersona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: revisiones del perfil (docente de la asignatura, Unidad de Investigación, Dirección)';

CREATE TABLE IF NOT EXISTS Designaciones (
  IdDesignacion            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente             INT UNSIGNED NOT NULL,
  IdPersona                INT UNSIGNED NOT NULL,
  Rol                      ENUM('TUTOR','TRIBUNAL_PRESIDENTE','TRIBUNAL_SECRETARIO','TRIBUNAL_VOCAL','ASESOR_INSTITUCIONAL','MONITOR') NOT NULL,
  Estado                   ENUM('PROPUESTA','ACEPTADA','RECHAZADA','REEMPLAZADA') NOT NULL DEFAULT 'PROPUESTA',
  NroResolucion            VARCHAR(40) NULL,
  FechaDesignacion         DATE        NOT NULL,
  FechaAceptacion          DATE        NULL,
  IdDesignacionReemplazada INT UNSIGNED NULL,
  Motivo                   VARCHAR(255) NULL,
  CodigoUsuario            INT UNSIGNED NOT NULL,
  FechaHoraRegistro        DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (IdDesignacion),
  KEY IX_Designaciones_Expediente (IdExpediente, Rol, Estado),
  KEY IX_Designaciones_Persona (IdPersona),
  CONSTRAINT FK_Designaciones_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_Designaciones_Personas FOREIGN KEY (IdPersona) REFERENCES Personas (IdPersona),
  CONSTRAINT FK_Designaciones_Reemplazada FOREIGN KEY (IdDesignacionReemplazada) REFERENCES Designaciones (IdDesignacion),
  CONSTRAINT FK_Designaciones_Usuarios FOREIGN KEY (CodigoUsuario) REFERENCES Usuarios (CodigoUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: tutor y tribunal con aceptación o reemplazo. Valida conflictos de interés (RF-06)';

-- ---------- Fase III: desarrollo y prácticas ----------
CREATE TABLE IF NOT EXISTS InformesAvance (
  IdInforme           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente        INT UNSIGNED NOT NULL,
  NumeroInforme       TINYINT UNSIGNED NOT NULL,
  FechaEntrega        DATE         NOT NULL,
  IdDocumento         INT UNSIGNED NULL,
  IdPersonaDocente    INT UNSIGNED NULL,
  Estado              ENUM('ENTREGADO','OBSERVADO','CONFORME') NOT NULL DEFAULT 'ENTREGADO',
  FechaProximoAvance  DATE         NULL,
  Observaciones       VARCHAR(1000) NULL,
  CodigoUsuario       INT UNSIGNED NOT NULL,
  PRIMARY KEY (IdInforme),
  UNIQUE KEY UQ_Informes (IdExpediente, NumeroInforme),
  CONSTRAINT FK_Informes_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_Informes_Documentos  FOREIGN KEY (IdDocumento) REFERENCES DocumentosExpediente (IdDocumento),
  CONSTRAINT FK_Informes_Docente     FOREIGN KEY (IdPersonaDocente) REFERENCES Personas (IdPersona),
  CONSTRAINT FK_Informes_Usuarios    FOREIGN KEY (CodigoUsuario) REFERENCES Usuarios (CodigoUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: control de avances con el docente de la asignatura (RF-08)';

CREATE TABLE IF NOT EXISTS ConveniosInstitucionales (
  IdConvenio        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  NombreInstitucion VARCHAR(200) NOT NULL,
  TipoInstitucion   ENUM('PUBLICA','PRIVADA','UNIVERSITARIA','OTRA') NOT NULL DEFAULT 'PUBLICA',
  NroConvenio       VARCHAR(60)  NULL,
  FechaInicio       DATE         NOT NULL,
  FechaVencimiento  DATE         NULL,
  CodigoCarrera     INT UNSIGNED NULL,
  Gestion           SMALLINT UNSIGNED NULL,
  CuposDisponibles  SMALLINT UNSIGNED NULL,
  Contacto          VARCHAR(150) NULL,
  Telefono          VARCHAR(30)  NULL,
  Email             VARCHAR(150) NULL,
  Estado            ENUM('VIGENTE','VENCIDO','SUSPENDIDO') NOT NULL DEFAULT 'VIGENTE',
  Observaciones     VARCHAR(500) NULL,
  PRIMARY KEY (IdConvenio),
  KEY IX_Convenios_Carrera (CodigoCarrera, Estado),
  CONSTRAINT FK_Convenios_Carreras FOREIGN KEY (CodigoCarrera) REFERENCES Carreras (CodigoCarrera)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: convenios con empresas e instituciones y sus cupos (RF-19)';

CREATE TABLE IF NOT EXISTS SeguimientoPracticas (
  IdSeguimiento         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente          INT UNSIGNED NOT NULL,
  IdConvenio            INT UNSIGNED NULL,
  FechaInicio           DATE         NOT NULL,
  FechaFin              DATE         NULL,
  HorasPlanificadas     SMALLINT UNSIGNED NULL,
  HorasCumplidas        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PorcentajeAsistencia  DECIMAL(5,2) NULL,
  Estado                ENUM('EN_CURSO','CUMPLIDO','INCUMPLIDO') NOT NULL DEFAULT 'EN_CURSO',
  Observaciones         VARCHAR(500) NULL,
  CodigoUsuario         INT UNSIGNED NOT NULL,
  FechaHoraRegistro     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (IdSeguimiento),
  KEY IX_SegPracticas_Expediente (IdExpediente),
  CONSTRAINT FK_SegPracticas_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_SegPracticas_Convenios   FOREIGN KEY (IdConvenio) REFERENCES ConveniosInstitucionales (IdConvenio),
  CONSTRAINT FK_SegPracticas_Usuarios    FOREIGN KEY (CodigoUsuario) REFERENCES Usuarios (CodigoUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: control del 70% de asistencia/avance en Trabajo Dirigido y Pasantía';

CREATE TABLE IF NOT EXISTS EvaluacionesExternas (
  IdEvaluacionExterna INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente        INT UNSIGNED NOT NULL,
  IdConvenio          INT UNSIGNED NULL,
  IdPersonaEvaluador  INT UNSIGNED NULL,
  NombreEvaluador     VARCHAR(150) NULL COMMENT 'Si el evaluador externo no está en Personas',
  Nota                DECIMAL(5,2) NOT NULL,
  PorcentajePeso      DECIMAL(5,2) NOT NULL DEFAULT 30.00,
  IdDocumento         INT UNSIGNED NULL,
  FechaEvaluacion     DATE         NOT NULL,
  RegistradoPor       INT UNSIGNED NOT NULL,
  FechaHoraRegistro   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Observaciones       VARCHAR(500) NULL,
  PRIMARY KEY (IdEvaluacionExterna),
  KEY IX_EvalExt_Expediente (IdExpediente),
  CONSTRAINT FK_EvalExt_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_EvalExt_Convenios   FOREIGN KEY (IdConvenio) REFERENCES ConveniosInstitucionales (IdConvenio),
  CONSTRAINT FK_EvalExt_Personas    FOREIGN KEY (IdPersonaEvaluador) REFERENCES Personas (IdPersona),
  CONSTRAINT FK_EvalExt_Documentos  FOREIGN KEY (IdDocumento) REFERENCES DocumentosExpediente (IdDocumento),
  CONSTRAINT FK_EvalExt_Usuarios    FOREIGN KEY (RegistradoPor) REFERENCES Usuarios (CodigoUsuario),
  CONSTRAINT CK_EvalExt_Nota CHECK (Nota BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: evaluación de empresa o monitor (hasta 30%), cargada por la Secretaría';

-- ---------- Fase IV: tribunal, observaciones, defensas ----------
CREATE TABLE IF NOT EXISTS DictamenesTribunal (
  IdDictamen         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente       INT UNSIGNED NOT NULL,
  IdDesignacion      INT UNSIGNED NOT NULL COMMENT 'Miembro del tribunal que emite el dictamen',
  Ronda              TINYINT UNSIGNED NOT NULL DEFAULT 1,
  IdDocumentoRevisado INT UNSIGNED NULL,
  Dictamen           ENUM('APROBADO','OBSERVADO','RECHAZADO') NULL COMMENT 'Aprobado directo, con observaciones subsanables, rechazado',
  NotaDocumental     DECIMAL(5,2) NULL COMMENT 'Rubro I (Proyecto de Grado)',
  Observaciones      TEXT NULL,
  FechaAsignacion    DATE         NOT NULL,
  FechaLimite        DATE         NULL,
  FechaDictamen      DATETIME     NULL,
  IdDocumentoDictamen INT UNSIGNED NULL,
  PRIMARY KEY (IdDictamen),
  UNIQUE KEY UQ_Dictamenes (IdDesignacion, Ronda),
  KEY IX_Dictamenes_Expediente (IdExpediente, Ronda),
  CONSTRAINT FK_Dictamenes_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_Dictamenes_Designaciones FOREIGN KEY (IdDesignacion) REFERENCES Designaciones (IdDesignacion),
  CONSTRAINT FK_Dictamenes_DocRevisado FOREIGN KEY (IdDocumentoRevisado) REFERENCES DocumentosExpediente (IdDocumento),
  CONSTRAINT FK_Dictamenes_DocDictamen FOREIGN KEY (IdDocumentoDictamen) REFERENCES DocumentosExpediente (IdDocumento),
  CONSTRAINT CK_Dictamenes_Nota CHECK (NotaDocumental IS NULL OR (NotaDocumental BETWEEN 0 AND 100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: dictamen individual de cada miembro del tribunal por ronda (RF-10)';

CREATE TABLE IF NOT EXISTS Observaciones (
  IdObservacion          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente           INT UNSIGNED NOT NULL,
  Etapa                  ENUM('PERFIL','DESARROLLO','BORRADOR','DEFENSA','CIERRE','CORRECCION_NOTA') NOT NULL,
  Origen                 VARCHAR(40)  NOT NULL COMMENT 'TUTOR, TRIBUNAL, UNIDAD_INVESTIGACION, DIRECCION, DECANATO, TITULOS, etc.',
  IdPersonaAutor         INT UNSIGNED NULL,
  IdDictamen             INT UNSIGNED NULL,
  Texto                  TEXT NOT NULL,
  Estado                 ENUM('ABIERTA','SUBSANADA','VALIDADA','CERRADA') NOT NULL DEFAULT 'ABIERTA',
  FechaHora              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FechaSubsanacion       DATETIME     NULL,
  IdDocumentoSubsanacion INT UNSIGNED NULL,
  CodigoUsuarioValida    INT UNSIGNED NULL,
  FechaHoraValidacion    DATETIME     NULL,
  PRIMARY KEY (IdObservacion),
  KEY IX_Observaciones_Expediente (IdExpediente, Estado),
  CONSTRAINT FK_Observaciones_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_Observaciones_Personas FOREIGN KEY (IdPersonaAutor) REFERENCES Personas (IdPersona),
  CONSTRAINT FK_Observaciones_Dictamenes FOREIGN KEY (IdDictamen) REFERENCES DictamenesTribunal (IdDictamen),
  CONSTRAINT FK_Observaciones_DocSubsanacion FOREIGN KEY (IdDocumentoSubsanacion) REFERENCES DocumentosExpediente (IdDocumento),
  CONSTRAINT FK_Observaciones_Valida FOREIGN KEY (CodigoUsuarioValida) REFERENCES Usuarios (CodigoUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: observaciones, subsanaciones y validaciones de todos los actores';

CREATE TABLE IF NOT EXISTS EventosEvaluacion (
  IdEvento              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente          INT UNSIGNED NOT NULL,
  Tipo                  ENUM('PREDEFENSA','DEFENSA_PUBLICA','DEFENSA_MONOGRAFIA','EXAMEN_GRADO','EXAMEN_CONTENIDOS','CASO_CLINICO','DEMOSTRACION_TECNICA') NOT NULL,
  Intento               TINYINT UNSIGNED NOT NULL DEFAULT 1,
  FechaHoraProgramada   DATETIME     NULL,
  Lugar                 VARCHAR(150) NULL,
  Turno                 VARCHAR(30)  NULL COMMENT 'Examen de grado',
  Balota                VARCHAR(30)  NULL COMMENT 'Examen de grado',
  Estado                ENUM('PROGRAMADO','REALIZADO','SUSPENDIDO','REPROGRAMADO') NOT NULL DEFAULT 'PROGRAMADO',
  Resultado             ENUM('APROBADO','REPROBADO') NULL,
  NotaFinal             DECIMAL(5,2) NULL COMMENT 'Transcrita desde el acta física',
  NroActa               VARCHAR(40)  NULL,
  IdDocumentoActa       INT UNSIGNED NULL,
  IdDocumentoAutoDecanal INT UNSIGNED NULL,
  ProgramadoPor         INT UNSIGNED NULL,
  TranscritoPor         INT UNSIGNED NULL,
  FechaHoraTranscripcion DATETIME    NULL,
  Observaciones         VARCHAR(1000) NULL COMMENT 'Observaciones dictadas al final de la defensa (van al informe)',
  PRIMARY KEY (IdEvento),
  UNIQUE KEY UQ_Eventos (IdExpediente, Tipo, Intento),
  KEY IX_Eventos_Fecha (FechaHoraProgramada),
  CONSTRAINT FK_Eventos_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_Eventos_DocActa FOREIGN KEY (IdDocumentoActa) REFERENCES DocumentosExpediente (IdDocumento),
  CONSTRAINT FK_Eventos_DocAuto FOREIGN KEY (IdDocumentoAutoDecanal) REFERENCES DocumentosExpediente (IdDocumento),
  CONSTRAINT FK_Eventos_Programa FOREIGN KEY (ProgramadoPor) REFERENCES Usuarios (CodigoUsuario),
  CONSTRAINT FK_Eventos_Transcribe FOREIGN KEY (TranscritoPor) REFERENCES Usuarios (CodigoUsuario),
  CONSTRAINT CK_Eventos_Nota CHECK (NotaFinal IS NULL OR (NotaFinal BETWEEN 0 AND 100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: defensas y exámenes: programación antes, resultado transcrito después (RF-11, 12, 24)';

-- ---------- Diplomado ----------
CREATE TABLE IF NOT EXISTS DiplomadoInscripciones (
  IdInscripcionDiplomado INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente           INT UNSIGNED NOT NULL,
  CodigoProgramaPostgrado INT UNSIGNED NULL COMMENT 'Código en Posgrado/CEPI',
  NombrePrograma         VARCHAR(200) NOT NULL,
  Gestion                SMALLINT UNSIGNED NULL,
  FechaInicio            DATE NULL,
  FechaFin               DATE NULL,
  NotaColegiatura        DECIMAL(5,2) NULL,
  RutaFinal              ENUM('MONOGRAFIA','EXAMEN_CONTENIDOS','EXCELENCIA') NULL COMMENT 'Se define según la nota de la colegiatura',
  Estado                 ENUM('INSCRITO','EN_CURSO','APROBADO','REPROBADO','ABANDONO') NOT NULL DEFAULT 'INSCRITO',
  PRIMARY KEY (IdInscripcionDiplomado),
  UNIQUE KEY UQ_Diplomado_Expediente (IdExpediente),
  CONSTRAINT FK_Diplomado_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: seguimiento del Diplomado como modalidad (Panel 10)';

CREATE TABLE IF NOT EXISTS DiplomadoModulos (
  IdModulo               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdInscripcionDiplomado INT UNSIGNED NOT NULL,
  NumeroModulo           TINYINT UNSIGNED NOT NULL,
  NombreModulo           VARCHAR(200) NOT NULL,
  Nota                   DECIMAL(5,2) NULL,
  Aprobado               TINYINT(1)   NOT NULL DEFAULT 0,
  FechaAprobacion        DATE NULL,
  PRIMARY KEY (IdModulo),
  UNIQUE KEY UQ_DiplomadoModulos (IdInscripcionDiplomado, NumeroModulo),
  CONSTRAINT FK_DiplomadoModulos_Inscripcion FOREIGN KEY (IdInscripcionDiplomado)
    REFERENCES DiplomadoInscripciones (IdInscripcionDiplomado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: módulos cursados del diplomado';

-- ---------- Plazos y alertas ----------
CREATE TABLE IF NOT EXISTS PlazosExpediente (
  IdPlazo               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente          INT UNSIGNED NOT NULL,
  Tipo                  ENUM('REVISION_TRIBUNAL','SUBSANACION','ENTREGA_INFORME','INACTIVIDAD','VIGENCIA_PERFIL','SOLVENCIA','MAXIMO_MODALIDAD','OTRO') NOT NULL,
  FechaInicio           DATE         NOT NULL,
  FechaVencimiento      DATE         NOT NULL,
  DiasHabiles           TINYINT(1)   NOT NULL DEFAULT 1,
  Estado                ENUM('VIGENTE','CUMPLIDO','VENCIDO','PAUSADO','PRORROGADO') NOT NULL DEFAULT 'VIGENTE',
  Semaforo              ENUM('VERDE','AMARILLO','ROJO','AZUL','GRIS') NOT NULL DEFAULT 'VERDE',
  IdPersonaResponsable  INT UNSIGNED NULL COMMENT 'A quién se le exige (estudiante, tribunal, etc.)',
  IdAutorizacionProrroga INT UNSIGNED NULL,
  FechaCumplimiento     DATE         NULL,
  Observaciones         VARCHAR(255) NULL,
  PRIMARY KEY (IdPlazo),
  KEY IX_Plazos_Vencimiento (Estado, FechaVencimiento),
  KEY IX_Plazos_Expediente (IdExpediente),
  CONSTRAINT FK_Plazos_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_Plazos_Responsable FOREIGN KEY (IdPersonaResponsable) REFERENCES Personas (IdPersona),
  CONSTRAINT FK_Plazos_Prorroga FOREIGN KEY (IdAutorizacionProrroga) REFERENCES AutorizacionesExcepciones (IdAutorizacion),
  CONSTRAINT CK_Plazos_Fechas CHECK (FechaVencimiento >= FechaInicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: vencimientos y semáforo por plazo (RF-13, RF-14)';

CREATE TABLE IF NOT EXISTS Notificaciones (
  IdNotificacion        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente          INT UNSIGNED NULL,
  IdPersonaDestinatario INT UNSIGNED NOT NULL,
  Canal                 ENUM('CORREO','PORTAL') NOT NULL DEFAULT 'CORREO',
  IdPlantilla           INT UNSIGNED NULL,
  Asunto                VARCHAR(200) NOT NULL,
  Mensaje               TEXT NOT NULL,
  Estado                ENUM('PENDIENTE','ENVIADA','FALLIDA','LEIDA') NOT NULL DEFAULT 'PENDIENTE',
  FechaHoraProgramada   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FechaHoraEnvio        DATETIME     NULL,
  Intentos              TINYINT UNSIGNED NOT NULL DEFAULT 0,
  Error                 VARCHAR(255) NULL,
  PRIMARY KEY (IdNotificacion),
  KEY IX_Notificaciones_Cola (Estado, FechaHoraProgramada),
  KEY IX_Notificaciones_Destinatario (IdPersonaDestinatario),
  CONSTRAINT FK_Notificaciones_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_Notificaciones_Personas FOREIGN KEY (IdPersonaDestinatario) REFERENCES Personas (IdPersona),
  CONSTRAINT FK_Notificaciones_Plantillas FOREIGN KEY (IdPlantilla) REFERENCES Plantillas (IdPlantilla)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: cola de alertas con estado de envío (RF-14)';

-- ---------- Fase V: cierre y transferencia ----------
CREATE TABLE IF NOT EXISTS CierreChecklist (
  IdCierre              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente          INT UNSIGNED NOT NULL,
  EjemplaresEntregados  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  IdDocumentoFinal      INT UNSIGNED NULL COMMENT 'PDF definitivo',
  SolvenciaFechaEmision DATETIME     NULL,
  SolvenciaVigenteHasta DATETIME     NULL COMMENT 'Emisión + vigencia configurada (48 h)',
  IdDocumentoSolvencia  INT UNSIGNED NULL,
  ActaRegistrada        TINYINT(1)   NOT NULL DEFAULT 0,
  Completo              TINYINT(1)   NOT NULL DEFAULT 0,
  RecepcionFisicaPor    INT UNSIGNED NULL,
  FechaHoraRecepcion    DATETIME     NULL,
  Observaciones         VARCHAR(500) NULL,
  PRIMARY KEY (IdCierre),
  UNIQUE KEY UQ_Cierre_Expediente (IdExpediente),
  CONSTRAINT FK_Cierre_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_Cierre_DocFinal FOREIGN KEY (IdDocumentoFinal) REFERENCES DocumentosExpediente (IdDocumento),
  CONSTRAINT FK_Cierre_DocSolvencia FOREIGN KEY (IdDocumentoSolvencia) REFERENCES DocumentosExpediente (IdDocumento),
  CONSTRAINT FK_Cierre_Recibe FOREIGN KEY (RecepcionFisicaPor) REFERENCES Usuarios (CodigoUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: checklist de entregables y solvencia (RF-16)';

CREATE TABLE IF NOT EXISTS TransferenciasTitulos (
  IdTransferencia     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente        INT UNSIGNED NOT NULL,
  FechaHoraEnvio      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CodigoUsuarioEnvio  INT UNSIGNED NOT NULL,
  HashPaquete         CHAR(64)     NOT NULL,
  Estado              ENUM('PREPARADO','ENVIADO','RECIBIDO','OBSERVADO') NOT NULL DEFAULT 'PREPARADO',
  ReferenciaExterna   VARCHAR(60)  NULL COMMENT 'IdTramite del trámite creado en Títulos',
  FechaHoraRecepcion  DATETIME     NULL,
  Observaciones       VARCHAR(500) NULL,
  PRIMARY KEY (IdTransferencia),
  KEY IX_Transferencias_Expediente (IdExpediente),
  CONSTRAINT FK_Transferencias_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_Transferencias_Usuarios FOREIGN KEY (CodigoUsuarioEnvio) REFERENCES Usuarios (CodigoUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: paquete enviado a la Sección Títulos con hash (RF-17)';

CREATE TABLE IF NOT EXISTS TrabajosFinales (
  IdTrabajoFinal  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente    INT UNSIGNED NOT NULL,
  Titulo          VARCHAR(400) NOT NULL,
  IdPersonaAutor  INT UNSIGNED NOT NULL,
  IdPersonaTutor  INT UNSIGNED NULL,
  CodigoCarrera   INT UNSIGNED NOT NULL,
  Anio            SMALLINT UNSIGNED NOT NULL,
  Resumen         TEXT NULL,
  PalabrasClave   VARCHAR(250) NULL,
  IdDocumento     INT UNSIGNED NOT NULL,
  Publico         TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (IdTrabajoFinal),
  UNIQUE KEY UQ_TrabajosFinales_Expediente (IdExpediente),
  KEY IX_TrabajosFinales_Carrera (CodigoCarrera, Anio),
  FULLTEXT KEY FT_TrabajosFinales (Titulo, Resumen, PalabrasClave),
  CONSTRAINT FK_TrabajosFinales_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_TrabajosFinales_Autor FOREIGN KEY (IdPersonaAutor) REFERENCES Personas (IdPersona),
  CONSTRAINT FK_TrabajosFinales_Tutor FOREIGN KEY (IdPersonaTutor) REFERENCES Personas (IdPersona),
  CONSTRAINT FK_TrabajosFinales_Carreras FOREIGN KEY (CodigoCarrera) REFERENCES Carreras (CodigoCarrera),
  CONSTRAINT FK_TrabajosFinales_Documentos FOREIGN KEY (IdDocumento) REFERENCES DocumentosExpediente (IdDocumento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: PDF definitivo para el buscador histórico (RF-21)';

-- ---------- Auditoría ----------
CREATE TABLE IF NOT EXISTS BitacoraAuditoria (
  IdBitacora     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  FechaHora      DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  CodigoUsuario  INT UNSIGNED NULL COMMENT 'NULL = acción del sistema',
  IdExpediente   INT UNSIGNED NULL,
  Accion         VARCHAR(40)  NOT NULL,
  Entidad        VARCHAR(60)  NOT NULL,
  IdEntidad      VARCHAR(40)  NULL,
  ValorAnterior  JSON NULL,
  ValorNuevo     JSON NULL,
  DireccionIP    VARCHAR(45)  NULL,
  HashAnterior   CHAR(64)     NULL,
  HashRegistro   CHAR(64)     NOT NULL COMMENT 'Hash encadenado con el registro previo',
  PRIMARY KEY (IdBitacora),
  KEY IX_Bitacora_Expediente (IdExpediente, FechaHora),
  KEY IX_Bitacora_Usuario (CodigoUsuario, FechaHora),
  KEY IX_Bitacora_Entidad (Entidad, IdEntidad),
  CONSTRAINT FK_Bitacora_Usuarios FOREIGN KEY (CodigoUsuario) REFERENCES Usuarios (CodigoUsuario),
  CONSTRAINT FK_Bitacora_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='NUEVA: bitácora inmutable con hash encadenado (RNF-02, RNF-08). Bloqueada por triggers';

-- =====================================================================
--  PARTE 4 - INTERNADO ROTATORIO Y CALIFICACIONES DE GRADUACIÓN
-- =====================================================================

CREATE TABLE IF NOT EXISTS Hospitales (
  IdHospital    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  Nombre        VARCHAR(200) NOT NULL,
  Departamento  VARCHAR(60)  NULL,
  Lugar         VARCHAR(120) NULL,
  Direccion     VARCHAR(200) NULL,
  Telefono      VARCHAR(30)  NULL,
  FechaConvenio DATE         NULL,
  Estado        TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (IdHospital),
  UNIQUE KEY UQ_Hospitales (Nombre, Lugar)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: Hospitales (simplificada)';

CREATE TABLE IF NOT EXISTS Especialidades (
  IdEspecialidad     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  CodigoCarrera      INT UNSIGNED NOT NULL,
  NumeroPlanEstudios INT UNSIGNED NOT NULL,
  Sigla              VARCHAR(10)  NOT NULL,
  Nombre             VARCHAR(120) NOT NULL,
  Orden              TINYINT UNSIGNED NULL,
  Estado             TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (IdEspecialidad),
  UNIQUE KEY UQ_Especialidades (CodigoCarrera, NumeroPlanEstudios, Sigla),
  CONSTRAINT FK_Especialidades_Planes FOREIGN KEY (CodigoCarrera, NumeroPlanEstudios)
    REFERENCES PlanesEstudios (CodigoCarrera, NumeroPlanEstudios)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: Especialidades (áreas de rotación)';

CREATE TABLE IF NOT EXISTS RotacionesInternado (
  IdRotacionInternado INT UNSIGNED NOT NULL AUTO_INCREMENT,
  Nombre              VARCHAR(100) NOT NULL,
  Tipo                VARCHAR(30)  NULL,
  Semestre            TINYINT UNSIGNED NULL,
  FechaInicio         DATE         NOT NULL,
  FechaFin            DATE         NOT NULL,
  Gestion             SMALLINT UNSIGNED NOT NULL,
  Estado              TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (IdRotacionInternado),
  UNIQUE KEY UQ_RotacionesInternado (Nombre, Gestion, Semestre),
  CONSTRAINT CK_RotacionesInternado_Fechas CHECK (FechaFin >= FechaInicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: RotacionesInternado';

CREATE TABLE IF NOT EXISTS Plazas (
  IdPlaza             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdHospital          INT UNSIGNED NOT NULL,
  IdEspecialidad      INT UNSIGNED NOT NULL,
  IdRotacionInternado INT UNSIGNED NOT NULL,
  NumeroPlazas        SMALLINT UNSIGNED NOT NULL,
  Gestion             SMALLINT UNSIGNED NOT NULL,
  Estado              TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (IdPlaza),
  UNIQUE KEY UQ_Plazas (IdHospital, IdEspecialidad, IdRotacionInternado),
  CONSTRAINT FK_Plazas_Hospitales    FOREIGN KEY (IdHospital)          REFERENCES Hospitales (IdHospital),
  CONSTRAINT FK_Plazas_Especialidades FOREIGN KEY (IdEspecialidad)     REFERENCES Especialidades (IdEspecialidad),
  CONSTRAINT FK_Plazas_Rotaciones    FOREIGN KEY (IdRotacionInternado) REFERENCES RotacionesInternado (IdRotacionInternado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: Plazas + HospitalesRecintos (plazas por centro, especialidad y rotación)';

CREATE TABLE IF NOT EXISTS ProgramacionesInternado (
  IdProgramacionInternado INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdExpediente            INT UNSIGNED NOT NULL,
  CU                      VARCHAR(15)  NOT NULL,
  IdPlaza                 INT UNSIGNED NOT NULL,
  MetodoAdjudicacion      ENUM('RANKING','SORTEO') NOT NULL DEFAULT 'RANKING',
  PuntajeAdjudicacion     DECIMAL(6,2) NULL,
  NumeroReserva           VARCHAR(30)  NULL,
  Estado                  ENUM('RESERVADA','ADJUDICADA','ANULADA','REUBICADA') NOT NULL DEFAULT 'RESERVADA',
  Observaciones           VARCHAR(500) NULL,
  CodigoUsuario           INT UNSIGNED NOT NULL,
  FechaHoraRegistro       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (IdProgramacionInternado),
  UNIQUE KEY UQ_ProgInternado (IdExpediente, IdPlaza),
  KEY IX_ProgInternado_CU (CU),
  CONSTRAINT FK_ProgInternado_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_ProgInternado_Universitarios FOREIGN KEY (CU) REFERENCES Universitarios (CU),
  CONSTRAINT FK_ProgInternado_Plazas FOREIGN KEY (IdPlaza) REFERENCES Plazas (IdPlaza),
  CONSTRAINT FK_ProgInternado_Usuarios FOREIGN KEY (CodigoUsuario) REFERENCES Usuarios (CodigoUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: ProgramacionesInternado + ReservasInternadoRotatorio (adjudicación de plaza)';

CREATE TABLE IF NOT EXISTS RotacionesEstudianteInternado (
  IdRotacionEstudiante    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  IdProgramacionInternado INT UNSIGNED NOT NULL,
  FechaInicio             DATE         NULL,
  FechaFin                DATE         NULL,
  PorcentajeAsistencia    DECIMAL(5,2) NULL,
  EvaluacionContinuada    DECIMAL(5,2) NULL,
  ExamenFinal             DECIMAL(5,2) NULL,
  NotaFinal               DECIMAL(5,2) NULL,
  NotaMunicipio           DECIMAL(5,2) NULL COMMENT 'Salud Pública',
  NotaDefensaMonografia   DECIMAL(5,2) NULL COMMENT 'Salud Pública (monografía rural)',
  IdPersonaMonitor        INT UNSIGNED NULL,
  Estado                  ENUM('PROGRAMADA','EN_CURSO','APROBADA','REPROBADA','REPROGRAMADA','LICENCIA') NOT NULL DEFAULT 'PROGRAMADA',
  IdRotacionOrigen        INT UNSIGNED NULL COMMENT 'Rotación de la que fue reubicado',
  Observaciones           VARCHAR(500) NULL,
  CodigoUsuario           INT UNSIGNED NOT NULL,
  FechaHoraRegistro       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (IdRotacionEstudiante),
  KEY IX_RotEstudiante_Prog (IdProgramacionInternado, Estado),
  CONSTRAINT FK_RotEstudiante_Prog FOREIGN KEY (IdProgramacionInternado) REFERENCES ProgramacionesInternado (IdProgramacionInternado),
  CONSTRAINT FK_RotEstudiante_Monitor FOREIGN KEY (IdPersonaMonitor) REFERENCES Personas (IdPersona),
  CONSTRAINT FK_RotEstudiante_Origen FOREIGN KEY (IdRotacionOrigen) REFERENCES RotacionesEstudianteInternado (IdRotacionEstudiante),
  CONSTRAINT FK_RotEstudiante_Usuarios FOREIGN KEY (CodigoUsuario) REFERENCES Usuarios (CodigoUsuario),
  CONSTRAINT CK_RotEstudiante_Notas CHECK (
    (NotaFinal IS NULL OR NotaFinal BETWEEN 0 AND 100) AND
    (ExamenFinal IS NULL OR ExamenFinal BETWEEN 0 AND 100) AND
    (PorcentajeAsistencia IS NULL OR PorcentajeAsistencia BETWEEN 0 AND 100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: CalificacionesInternado + EvaluacionesInternadoRotatorio + AsistenciaInternado + CalificacionesSaludPublicaInternado (resumen por rotación)';

-- ---------- Calificaciones finales de modalidades de graduación (destino de RF-15) ----------
CREATE TABLE IF NOT EXISTS CalificacionesModalidadesGraduacion (
  CU                      VARCHAR(15)  NOT NULL,
  CodigoModalidadCurso    INT UNSIGNED NOT NULL,
  GestionAcademica        VARCHAR(10)  NOT NULL,
  NroRegistro             INT UNSIGNED NOT NULL,
  IdExpediente            INT UNSIGNED NULL COMMENT 'NUEVO: vínculo con el expediente DTIC',
  Tema                    VARCHAR(400) NULL,
  FechaHoraDefensa        DATETIME     NULL,
  Nota                    DECIMAL(5,2) NULL,
  NroResolucion           VARCHAR(40)  NULL,
  ArchivoDigital          VARCHAR(500) NULL,
  FechaHoraArchivo        DATETIME     NULL,
  ObsRegistro             VARCHAR(500) NULL,
  CodigoUsuarioRegistro   INT UNSIGNED NOT NULL,
  FechaHoraRegistro       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  HashRegistro            CHAR(64)     NULL,
  ObsValidacion           VARCHAR(500) NULL,
  CodigoUsuarioValidador  INT UNSIGNED NULL,
  FechaHoraValidacion     DATETIME     NULL,
  HashValidacion          CHAR(64)     NULL,
  PRIMARY KEY (CU, CodigoModalidadCurso, GestionAcademica, NroRegistro),
  KEY IX_CalifGrad_Expediente (IdExpediente),
  CONSTRAINT FK_CalifGrad_Universitarios FOREIGN KEY (CU) REFERENCES Universitarios (CU),
  CONSTRAINT FK_CalifGrad_Modalidades FOREIGN KEY (CodigoModalidadCurso) REFERENCES ModalidadesTitulacion (CodigoModalidadCurso),
  CONSTRAINT FK_CalifGrad_Expedientes FOREIGN KEY (IdExpediente) REFERENCES Expedientes (IdExpediente),
  CONSTRAINT FK_CalifGrad_Registra FOREIGN KEY (CodigoUsuarioRegistro) REFERENCES Usuarios (CodigoUsuario),
  CONSTRAINT FK_CalifGrad_Valida FOREIGN KEY (CodigoUsuarioValidador) REFERENCES Usuarios (CodigoUsuario),
  CONSTRAINT CK_CalifGrad_Nota CHECK (Nota IS NULL OR (Nota BETWEEN 0 AND 100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: CalificacionesModalidadesGraduacion (casi idéntica). Se migra al SEA (RF-15). Validada = no editable';

CREATE TABLE IF NOT EXISTS DetalleCalificacionesModalidadesGraduacion (
  CU                      VARCHAR(15)  NOT NULL,
  CodigoModalidadCurso    INT UNSIGNED NOT NULL,
  GestionAcademica        VARCHAR(10)  NOT NULL,
  NroRegistro             INT UNSIGNED NOT NULL,
  IdNota                  INT UNSIGNED NOT NULL,
  Detalle                 VARCHAR(150) NOT NULL COMMENT 'Rubro: documento, defensa, evaluación institucional, etc.',
  Nota                    DECIMAL(5,2) NULL,
  IdPersonaMonitor        INT UNSIGNED NULL,
  CodigoUsuarioRegistro   INT UNSIGNED NOT NULL,
  FechaHoraRegistro       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  HashRegistro            CHAR(64)     NULL,
  CodigoUsuarioValidador  INT UNSIGNED NULL,
  FechaHoraValidacion     DATETIME     NULL,
  HashValidacion          CHAR(64)     NULL,
  PRIMARY KEY (CU, CodigoModalidadCurso, GestionAcademica, NroRegistro, IdNota),
  CONSTRAINT FK_DetCalifGrad_Calif FOREIGN KEY (CU, CodigoModalidadCurso, GestionAcademica, NroRegistro)
    REFERENCES CalificacionesModalidadesGraduacion (CU, CodigoModalidadCurso, GestionAcademica, NroRegistro),
  CONSTRAINT FK_DetCalifGrad_Monitor FOREIGN KEY (IdPersonaMonitor) REFERENCES Personas (IdPersona),
  CONSTRAINT FK_DetCalifGrad_Registra FOREIGN KEY (CodigoUsuarioRegistro) REFERENCES Usuarios (CodigoUsuario),
  CONSTRAINT FK_DetCalifGrad_Valida FOREIGN KEY (CodigoUsuarioValidador) REFERENCES Usuarios (CodigoUsuario),
  CONSTRAINT CK_DetCalifGrad_Nota CHECK (Nota IS NULL OR (Nota BETWEEN 0 AND 100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='SEA: DetalleCalificacionesModalidadesGraduacion (detalle por rubro)';

-- =====================================================================
--  PARTE 5 - DATOS INICIALES DE CATÁLOGOS
-- =====================================================================

INSERT IGNORE INTO FasesTramite (CodigoFase, NombreFase, Orden) VALUES
 (1,'Habilitación y Admisión Académica',1),
 (2,'Registro de Tema, Perfil y Asignación de Tutor/Asesor',2),
 (3,'Desarrollo, Seguimiento de Avances y Prácticas',3),
 (4,'Evaluaciones, Revisión de Borrador y Defensas',4),
 (5,'Cierre Documental, Colación de Grado y Titulación',5);

INSERT IGNORE INTO Roles (NombreRol, Descripcion) VALUES
 ('Estudiante','Panel 1'),
 ('Docente de la Asignatura de Titulación','Panel 2'),
 ('Tutor Académico','Panel 3 (opcional, según carrera)'),
 ('Tribunal Evaluador','Panel 4'),
 ('Secretaría de Carrera / Kardex','Panel 5'),
 ('Dirección de Carrera','Panel 6'),
 ('Decanato','Panel 7'),
 ('Secretaría de Internado / Jefatura de Internado','Panel 8'),
 ('Unidad de Investigación','Panel 9'),
 ('Posgrado / CEPI','Panel 10 (solo Diplomado)'),
 ('Sección de Títulos y Grados','Panel 11'),
 ('Administrador DTIC','Panel 12'),
 ('Autoridades Centrales (Rectorado)','Vista adicional de solo consulta');

INSERT IGNORE INTO ModalidadesTitulacion (NombreModalidad, Sigla, Categoria, CodigoModalidadCurso) VALUES
 -- CodigoModalidadCurso: valores PROVISIONALES (901-909). Reemplazar por los códigos reales del SEA.
 ('Tesis de Grado','TESIS','INVESTIGACION',901),
 ('Proyecto de Grado','PROYECTO','INVESTIGACION',902),
 ('Monografía','MONOGRAFIA','INVESTIGACION',903),
 ('Trabajo Dirigido','TRAB_DIRIGIDO','PRACTICA_LABORAL',904),
 ('Pasantía','PASANTIA','PRACTICA_LABORAL',905),
 ('Internado Rotatorio','INTERNADO','PRACTICA_LABORAL',906),
 ('Diplomado','DIPLOMADO','EVALUACIONES_ACADEMICAS',907),
 ('Examen de Grado','EXAMEN_GRADO','EVALUACIONES_ACADEMICAS',908),
 ('Excelencia Académica','EXCELENCIA','RECONOCIMIENTO_DESEMPENO',909);

-- Estados canónicos (válidos para todas las modalidades). Las etiquetas específicas por modalidad
-- se agregan después con CodigoModalidadTitulacion.
INSERT IGNORE INTO EstadosTramite (CodigoFase, CodigoModalidadTitulacion, NombreCanonico, Etiqueta, EsFinal, Orden) VALUES
 (1,NULL,'HABILITADO','Habilitado',0,10),
 (2,NULL,'PERFIL_EN_REVISION','Perfil en revisión',0,20),
 (2,NULL,'PERFIL_APROBADO','Perfil aprobado',0,30),
 (3,NULL,'EN_DESARROLLO','En desarrollo',0,40),
 (3,NULL,'BORRADOR_ENTREGADO','Borrador entregado',0,50),
 (4,NULL,'EN_REVISION_TRIBUNAL','En revisión del tribunal / pre-defensa',0,60),
 (4,NULL,'OBSERVADO','Observado',0,70),
 (4,NULL,'HABILITADO_DEFENSA','Habilitado para defensa',0,80),
 (4,NULL,'DEFENSA_PROGRAMADA','Defensa programada',0,90),
 (4,NULL,'APROBADO','Aprobado',0,100),
 (4,NULL,'REPROBADO','Reprobado',0,105),
 (4,NULL,'RECHAZADO','Rechazado / insuficiente',0,106),
 (5,NULL,'EJEMPLARES_ENTREGADOS','Ejemplares entregados',0,110),
 (5,NULL,'PASE_A_TITULOS','Pase a Sección Títulos',0,120),
 (5,NULL,'CONCLUIDO','Concluido',1,130),
 (NULL,NULL,'CONGELADO','Congelado por inactividad (Gris)',0,200),
 (NULL,NULL,'ABANDONADO','Inactivo por abandono',1,210),
 (NULL,NULL,'CAMBIO_MODALIDAD','Archivado por cambio de modalidad',1,220);

INSERT IGNORE INTO TiposDocumento (NombreTipoDocumento, Descripcion, GeneradoPorSistema) VALUES
 ('Solicitud / Memorial','Solicitud del estudiante',0),
 ('Perfil de investigación','Perfil o propuesta del trabajo',0),
 ('Certificado de No Duplicidad','Emitido por la Unidad de Investigación',0),
 ('Convenio / Carta de aceptación','Institución o empresa patrocinadora',0),
 ('Informe de avance','Entrega periódica ante el tutor o docente',0),
 ('Borrador del trabajo','Versión para revisión del tribunal',0),
 ('Dictamen del tribunal','Escaneo del dictamen firmado',0),
 ('Informe final','Informe final del trabajo o práctica',0),
 ('Evaluación de la institución externa','Empresa, hospital o monitor',0),
 ('Auto decanal de citación','Generado por el sistema',1),
 ('Acta de defensa o examen','Acta física escaneada',0),
 ('Resolución Decanal','Resolución (incluye Excelencia)',0),
 ('Solvencia','Control de solvencias (vigencia configurable)',0),
 ('PDF definitivo','Versión final para el repositorio',0),
 ('Ejemplares empastados (constancia)','Recepción de ejemplares físicos',0),
 ('Documento de ingreso a la modalidad','Requisito inicial de Fase I (según carrera)',0);

INSERT IGNORE INTO ParametrosSistema (Clave, Valor, Descripcion) VALUES
 ('DIAS_ALERTA_AMARILLO','5','Valor inicial: días antes del vencimiento para pasar a amarillo'),
 ('DIAS_GRACIA_POR_DEFECTO','15','Valor inicial: periodo de gracia tras el rojo'),
 ('DIAS_AVISO_PREVIO_ABANDONO','15','Valor inicial: aviso al Director antes del cierre automático por abandono'),
 ('SOLVENCIA_VIGENCIA_HORAS','48','Vigencia de la solvencia desde su emisión'),
 ('PORCENTAJE_ASISTENCIA_MIN_PRACTICAS','70','Trabajo Dirigido y Pasantía'),
 ('PORCENTAJE_MAX_EVAL_EXTERNA','30','Peso máximo de la evaluación institucional'),
 ('TAMANO_MAXIMO_ARCHIVO_MB','20','Límite por archivo'),
 ('EXTENSIONES_PERMITIDAS','pdf','PDF no protegido (RNF-14)');

-- Catálogos del SEA con valores PROVISIONALES: reemplazar por los reales al cargar los datos del SEA
INSERT IGNORE INTO NivelesAcademicos (NombreNivelAcademico, Tipo) VALUES
 ('Técnico Superior','PREGRADO'),('Licenciatura','PREGRADO');
INSERT IGNORE INTO EstadosCU (NombreEstadoCU) VALUES
 ('ACTIVO'),('INACTIVO'),('EGRESADO'),('TITULADO');
INSERT IGNORE INTO EstadosCalificacion (NombreEstadoCalificacion, EsAprobado) VALUES
 ('APROBADO',1),('REPROBADO',0),('ABANDONO',0),('NO PRESENTADO',0);

-- =====================================================================
--  PARTE 6 - REGLAS DE NEGOCIO (procedimiento y triggers)
--  Si el importador de phpMyAdmin falla aquí, importe todo lo anterior y
--  luego pegue esta sección en la pestaña SQL con el delimitador $$
-- =====================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS SP_ValidarConflictoInteres$$
CREATE PROCEDURE SP_ValidarConflictoInteres(
  IN pIdExpediente INT UNSIGNED,
  IN pIdPersona INT UNSIGNED,
  IN pRol VARCHAR(30),
  IN pIdDesignacionExcluir INT UNSIGNED)
BEGIN
  DECLARE vConteo INT DEFAULT 0;

  IF pRol IN ('TRIBUNAL_PRESIDENTE','TRIBUNAL_SECRETARIO','TRIBUNAL_VOCAL') THEN
    -- Regla 1: el docente de la asignatura de titulación del estudiante no puede ser su tribunal
    SELECT COUNT(*) INTO vConteo
      FROM Expedientes E
      JOIN DetallesProgramaciones DP ON DP.CU = E.CU
      JOIN MateriasPlanEstudios M
        ON M.CodigoCarrera = DP.CodigoCarrera AND M.NumeroPlanEstudios = DP.NumeroPlanEstudios
       AND M.SiglaMateria = DP.SiglaMateria AND M.EsAsignaturaTitulacion = 1
      JOIN MateriasDocentes MD
        ON MD.GestionAcademica = DP.GestionAcademica AND MD.CodigoModalidadCurso = DP.CodigoModalidadCurso
       AND MD.CodigoCarrera = DP.CodigoCarrera AND MD.NumeroPlanEstudios = DP.NumeroPlanEstudios
       AND MD.SiglaMateria = DP.SiglaMateria AND MD.Grupo = DP.Grupo
     WHERE E.IdExpediente = pIdExpediente AND MD.IdPersona = pIdPersona;
    IF vConteo > 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT =
        'Conflicto de interés: el docente de la asignatura de titulación no puede ser tribunal del mismo estudiante';
    END IF;

    -- Regla 2: el tutor del expediente no puede ser tribunal del mismo expediente
    SELECT COUNT(*) INTO vConteo FROM Designaciones D
     WHERE D.IdExpediente = pIdExpediente AND D.IdPersona = pIdPersona AND D.Rol = 'TUTOR'
       AND D.Estado IN ('PROPUESTA','ACEPTADA')
       AND (pIdDesignacionExcluir IS NULL OR D.IdDesignacion <> pIdDesignacionExcluir);
    IF vConteo > 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT =
        'Conflicto de interés: el tutor del expediente no puede ser tribunal del mismo estudiante';
    END IF;

  ELSEIF pRol = 'TUTOR' THEN
    SELECT COUNT(*) INTO vConteo FROM Designaciones D
     WHERE D.IdExpediente = pIdExpediente AND D.IdPersona = pIdPersona
       AND D.Rol IN ('TRIBUNAL_PRESIDENTE','TRIBUNAL_SECRETARIO','TRIBUNAL_VOCAL')
       AND D.Estado IN ('PROPUESTA','ACEPTADA')
       AND (pIdDesignacionExcluir IS NULL OR D.IdDesignacion <> pIdDesignacionExcluir);
    IF vConteo > 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT =
        'Conflicto de interés: quien integra el tribunal no puede ser tutor del mismo estudiante';
    END IF;
  END IF;
END$$

DROP TRIGGER IF EXISTS TR_Designaciones_BI$$
CREATE TRIGGER TR_Designaciones_BI BEFORE INSERT ON Designaciones
FOR EACH ROW
BEGIN
  IF NEW.Estado IN ('PROPUESTA','ACEPTADA') THEN
    CALL SP_ValidarConflictoInteres(NEW.IdExpediente, NEW.IdPersona, NEW.Rol, NULL);
  END IF;
END$$

DROP TRIGGER IF EXISTS TR_Designaciones_BU$$
CREATE TRIGGER TR_Designaciones_BU BEFORE UPDATE ON Designaciones
FOR EACH ROW
BEGIN
  IF NEW.Estado IN ('PROPUESTA','ACEPTADA')
     AND (NEW.IdPersona <> OLD.IdPersona OR NEW.Rol <> OLD.Rol OR OLD.Estado NOT IN ('PROPUESTA','ACEPTADA')) THEN
    CALL SP_ValidarConflictoInteres(NEW.IdExpediente, NEW.IdPersona, NEW.Rol, OLD.IdDesignacion);
  END IF;
END$$

-- Bitácora inmutable (RNF-08)
DROP TRIGGER IF EXISTS TR_Bitacora_BU$$
CREATE TRIGGER TR_Bitacora_BU BEFORE UPDATE ON BitacoraAuditoria
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La bitácora de auditoría es inmutable: no se permite modificar registros';
END$$

DROP TRIGGER IF EXISTS TR_Bitacora_BD$$
CREATE TRIGGER TR_Bitacora_BD BEFORE DELETE ON BitacoraAuditoria
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La bitácora de auditoría es inmutable: no se permite eliminar registros';
END$$

-- Nota de graduación validada = no editable, salvo flujo de corrección autorizado (RF-15).
-- La aplicación activa la excepción con:  SET @PermitirCorreccionNota = 1;  (solo tras autorización del Director)
DROP TRIGGER IF EXISTS TR_CalifGrad_BU$$
CREATE TRIGGER TR_CalifGrad_BU BEFORE UPDATE ON CalificacionesModalidadesGraduacion
FOR EACH ROW
BEGIN
  IF OLD.FechaHoraValidacion IS NOT NULL AND IFNULL(@PermitirCorreccionNota,0) <> 1
     AND (NOT (NEW.Nota <=> OLD.Nota) OR NOT (NEW.Tema <=> OLD.Tema)
          OR NOT (NEW.FechaHoraDefensa <=> OLD.FechaHoraDefensa) OR NOT (NEW.NroResolucion <=> OLD.NroResolucion)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT =
      'Registro validado: no es editable. Use el flujo excepcional de corrección con autorización del Director de Carrera';
  END IF;
END$$

DROP TRIGGER IF EXISTS TR_CalifGrad_BD$$
CREATE TRIGGER TR_CalifGrad_BD BEFORE DELETE ON CalificacionesModalidadesGraduacion
FOR EACH ROW
BEGIN
  IF OLD.FechaHoraValidacion IS NOT NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Registro validado: no se puede eliminar';
  END IF;
END$$

DROP TRIGGER IF EXISTS TR_DetCalifGrad_BU$$
CREATE TRIGGER TR_DetCalifGrad_BU BEFORE UPDATE ON DetalleCalificacionesModalidadesGraduacion
FOR EACH ROW
BEGIN
  IF OLD.FechaHoraValidacion IS NOT NULL AND IFNULL(@PermitirCorreccionNota,0) <> 1
     AND (NOT (NEW.Nota <=> OLD.Nota) OR NOT (NEW.Detalle <=> OLD.Detalle)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT =
      'Detalle validado: no es editable. Use el flujo excepcional de corrección';
  END IF;
END$$

DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;
```