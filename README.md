# Panel de Administración - Evaluación Directiva
**Universidad de León (UDL)**

Sistema web institucional de uso interno diseñado para automatizar el procesamiento masivo de respuestas de encuestas de evaluación a directores de facultad desde archivos Microsoft Excel (.xlsx / .xls), ejecutando análisis estadístico multivariable y generando reportes oficiales en formato PDF listos para impresión en tamaño Carta.

---

## Tabla de Contenidos
1. [Descripción General](#descripción-general)
2. [Stack Tecnológico](#stack-tecnológico)
3. [Módulos del Sistema](#módulos-del-sistema)
4. [Reportes PDF Oficiales (FPDF)](#reportes-pdf-oficiales-fpdf)
5. [Arquitectura de Directorios](#arquitectura-de-directorios)
6. [Instalación y Despliegue en Hostinger](#instalación-y-despliegue-en-hostinger)
7. [Reglas de Negocio y Algoritmos](#reglas-de-negocio-y-algoritmos)
8. [Seguridad y Buenas Prácticas](#seguridad-y-buenas-prácticas)

---

## Descripción General

El sistema permite a la coordinación académica de la Universidad de León procesar los resultados cuatrimestrales de evaluación de directores de facultad con total independencia tecnológica:
- **Cero dependencias de servicios externos:** Todo el procesamiento matemático, vinculación y renderizado de PDF se realiza localmente en el servidor.
- **Identidad Institucional UDL:** Diseño web responsivo basado en la paleta oficial (Verde `#007547`, Lima `#a2cf6d`, Oscuro `#0f392b`).
- **Resiliencia en producción:** Separación física estricta entre la lógica de negocio privada y el directorio web público.

---

## Stack Tecnológico

* **Backend:** PHP 8.x nativo (Arquitectura MVC, tipos estrictos `declare(strict_types=1);` y estándar PSR-4).
* **Base de Datos:** MySQL / MariaDB (Motor InnoDB, transacciones ACID, índices optimizados y prevención contra inyección SQL con PDO).
* **Lectura y Procesamiento de Excel:** `PhpOffice/PhpSpreadsheet` con filtro de lectura por lotes (*Chunking* con `IReadFilter`) para optimizar memoria RAM.
* **Motor de Generación PDF:** `FPDF` nativo calibrado en **tamaño Carta (`Letter`)** con codificación Windows-1252 para soporte completo de acentos y caracteres en español.
* **Frontend:** HTML5 semántico, Tailwind CSS (extendido institucional UDL), FontAwesome 6 y JavaScript Vanilla moderno (`fetch()` asíncrono, sin jQuery).
* **Infraestructura:** Compatible con entornos locales (XAMPP / PHP CLI) y hosting compartido en producción (**Hostinger** / LiteSpeed / Apache).

---

## Módulos del Sistema

1. **Autenticación y Seguridad de Cuenta:**
   - Inicio de sesión con contraseñas cifradas en `Bcrypt`.
   - Protección contra ataques CSRF con tokens únicos por sesión.
   - Módulo para cambio de contraseña del administrador.
   - Menú dropdown de usuario y menú hamburguesa responsivo (desktop colapsable con animación y móvil drawer).

2. **Gestión de Periodos de Evaluación:**
   - Alta de ciclos académicos (ej. `2026-3`, `2027-1`).
   - **Regla de Exclusividad:** Solo **un periodo** permanece con estatus `ACTIVO` a la vez; activar uno cierra automáticamente los demás.
   - Conteo en tiempo real de encuestas asociadas y eliminación transaccional en cascada.

3. **Carga e Importación de Encuestas (Excel):**
   - Validación estricta: solo se permite cargar encuestas en periodos que **aún no tienen un archivo procesado**, bloqueando sobreescrituras accidentales.
   - Dropzone interactivo con arrastrar y soltar.
   - Barra de progreso dinámica y bitácora en vivo de incidencias o filas omitidas.

4. **Motor Estadístico y Jerarquización (Ranking):**
   - Promedios individuales por pregunta (**P1 a P10**).
   - Promedio global y Desviación Estándar poblacional (`STDDEV_POP`).
   - Algoritmo de ranking competitivo institucional (con gestión de empates).
   - Tarjetas KPI: Promedio general, total encuestas analizadas, mejor puntuado y menor calificación.

5. **Catálogos Maestros Institucionales:**
   - Administración interactiva de **Directores de Facultad** (alta, edición, asignación de turno `T`, `M`, `V` y estatus).
   - **Buscador y Filtrado Avanzado Multi-criterio:** Búsqueda en tiempo real combinando texto libre, plantel, licenciatura, turno y estatus.
   - Catálogo de **Planteles** (asociados a Regiones I, II y III).
   - Catálogo de **Licenciaturas** con sus **Nomenclaturas Oficiales** (ej. `LAE`, `DER`, `CP`, `ARQ`).

6. **Comparativa Histórica entre Periodos:**
   - Contraste dinámico entre dos ciclos (Periodo Base vs Periodo Comparado).
   - Cálculo automático de variaciones (`+` / `-`) e indicador visual de tendencia (Mejora, Descenso, Estable).
   - Visualización de nomenclaturas institucionales en lugar de nombres extensos.
   - **Exportación en PDF** de la comparativa histórica.

---

## Reportes PDF Oficiales (FPDF)

Todos los reportes están calibrados en **tamaño Carta (`Letter`: 215.9 mm × 279.4 mm)** para impresión directa sin ajustes de escala:

| Reporte | Orientación | Descripción y Contenido |
| :--- | :--- | :--- |
| **Reporte General por Región** | Vertical (*Portrait*) | Logotipo UDL en color, título del periodo y región evaluada. Tabla con columnas `PLANTEL`, `CARRERA` (nomenclatura), `DIRECTOR DE FACULTAD` (fila individual por turno) y `PG`. Resumen inferior con **Promedio General Por Pregunta** y **Desviación Estándar**. |
| **Reporte Detallado por Plantel** | Horizontal (*Landscape*) | Logotipo UDL, título del plantel y bloque centralizado **TEMAS DE EVALUACIÓN DIRECTIVA** con las 10 preguntas ordenadas verticalmente con franja gris institucional. Tabla con `CARRERA`, `DIRECTOR DE FACULTAD`, desglose `P1` a `P10` y `PG`. Resumen al pie con promedios y desviaciones estándar por pregunta. |
| **Reporte de Comparativa Histórica** | Vertical (*Portrait*) | Logotipo UDL, contraste de ciclo base vs comparado, tabla con `DIRECTOR DE FACULTAD`, `PLANTEL`, `CARRERA` (nomenclatura), promedio de cada periodo y columna de diferencial resaltada por color según la tendencia. |

> **Configuración de preguntas P1 a P10:** Desacoplada en `config/preguntas.php` para modificar descripciones en cualquier momento sin alterar la base de datos.

---

## Arquitectura de Directorios

```text
evaluaciondirectiva_app/
├── evaluaciondirectiva/                  # Backend privado (FUERA de la raíz pública web)
│   ├── app/
│   │   ├── Controllers/                  # Auth, Catalogo, Import, Periodo, Report
│   │   ├── Core/                         # Cache, Logger, Router, Security
│   │   ├── Models/                       # Catalogo, Periodo, Respuesta, Usuario
│   │   └── Services/                     # ExcelImportService, PdfGeneratorService, StatisticsService
│   ├── config/
│   │   ├── app.php                       # Bootstrap, autoloader PSR-4, helpers globales
│   │   ├── database.php                  # PDO Singleton con respaldo dual (localhost / 127.0.0.1)
│   │   └── preguntas.php                 # Glosario configurable de preguntas P1-P10
│   ├── notes/
│   │   └── PROGRESS.md                   # Bitácora detallada de avances y control de cambios
│   ├── storage/                          # Almacenamiento interno con permisos 755
│   │   ├── cache/                        # Caché serializada con TTL
│   │   ├── logs/                         # Bitácoras rotativas diarias (app, database, reports)
│   │   └── uploads/                      # Archivos Excel temporales
│   ├── vendor/                           # Dependencias Composer (PhpSpreadsheet, Dotenv, etc.)
│   ├── views/                            # Plantillas HTML/PHP (layouts, auth, panel, reports)
│   └── .env                              # Credenciales de entorno
│
└── public_html/                          # Raíz pública expuesta a Internet
    └── evaluaciondirectiva/
        ├── index.php                     # Front Controller y enrutador central
        ├── .htaccess                     # Reglas de reescritura Apache/LiteSpeed
        ├── assets/                       # CSS (Tailwind extendido), JS (Vanilla) e imágenes oficiales UDL
        └── fpdf/                         # Librería FPDF (fpdf.php, fuentes y makefont)
```

---

## Instalación y Despliegue en Hostinger

1. **Importar la Base de Datos:**
   - En el panel de Hostinger (hPanel), ingresar a **Bases de datos MySQL** y crear la base de datos y usuario correspondientes.
   - Abrir **phpMyAdmin** e importar el script SQL inicial (`u665136406_evaluaciondir.sql`).

2. **Estructura de Archivos en el Servidor:**
   - Subir la carpeta privada `evaluaciondirectiva/` a la raíz del usuario (un nivel arriba de `public_html/`).
   - Subir el contenido de `public_html/evaluaciondirectiva/` dentro de la carpeta pública de tu dominio en Hostinger.
   - *Nota:* No es necesario subir scripts de desarrollo local como `.bat` ni archivos de prueba.

3. **Configuración de Variables de Entorno (`.env`):**
   Editar el archivo `evaluaciondirectiva/.env` con los datos de producción:
   ```env
   APP_NAME="Panel de Evaluación Directiva"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://tudominio.com

   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=u665136406_evaluaciondir
   DB_USER=u665136406_evaluaciondir
   DB_PASS=TuContraseñaReal
   ```

4. **Permisos de Almacenamiento:**
   Asegurar permisos `755` (o `775`) en la carpeta `evaluaciondirectiva/storage/` y sus subcarpetas (`cache/`, `logs/`, `uploads/`).

---

## Reglas de Negocio y Algoritmos

* **Normalización de Grupos y Turnos:**
  El sistema analiza el penúltimo dígito antes del guion (`-`) del grupo del alumno:
  - `x0x-x` ➔ **Matutino (`M`)**
  - `x1x-x` ➔ **Diurno (`D`)**
  - `x2x-x` ➔ **Vespertino (`V`)**
  - `x3x-x` ➔ **Nocturno (`N`)**
* **Vinculación con Directores:**
  - Alumnos en turno `M` o `D` se vinculan al director con turno `M` o `D`.
  - Alumnos en turno `V` o `N` se vinculan al director con turno `V` o `N`.
  - Si no existe división por turno para esa carrera, se vinculan al director de turno `T` (Todas).
  - Si no se encuentra director asignado, la respuesta se registra con `id_director = NULL` sin descartar la estadística de la carrera.
* **Escala y Normalización:**
  - Preguntas `P1` a `P10` se computan en escala de **5.0 a 10.0**.
  - Preguntas booleanas (`P4`, `P5`, `P6`) se normalizan automáticamente: `"Sí"` = 10, `"No"` = 5.

---

## Seguridad y Buenas Prácticas

* **Consultas Preparadas PDO:** `PDO::ATTR_EMULATE_PREPARES => false` forzando preparación en MySQL para blindar contra inyecciones SQL.
* **Aislamiento de Código:** Ningún script de lógica de negocio o credencial es accesible mediante peticiones directas del navegador.
* **Sesiones Seguras:** Flags `httponly`, `samesite=Lax` y regeneración periódica de identificador de sesión.
* **Logging y Caché Internos:** Registro diario de incidentes en `storage/logs/` sin exponer errores sensibles al usuario final.
