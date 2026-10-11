# Panel de Administración - Evaluación de Directivos

Sistema web de uso interno diseñado para automatizar el procesamiento de respuestas (encuestas de evaluación de directores) a partir de archivos Excel, realizando análisis estadístico automatizado y generación de reportes institucionales en PDF.

## Alcance y Objetivos (Semana 1)
Este repositorio contiene la arquitectura inicial, el modelo de datos y el motor de importación definidos durante la Semana 1. El objetivo principal es garantizar la integridad, normalización y procesamiento masivo de datos estadísticos.

## Stack Tecnológico
* **Backend:** PHP 8.x nativo (Patrón MVC).
* **Base de Datos:** MySQL (Motor InnoDB para transacciones ACID).
* **Lectura de Excel:** `PhpSpreadsheet` (Optimizando RAM mediante *Chunking* / `IReadFilter`).
* **Generación de PDF:** `Dompdf` / `mPDF`.
* **Seguridad:** PDO (Prevención SQL Injection), Bcrypt (Hash de contraseñas), separación estricta de directorio público.

## Arquitectura de Directorios (Despliegue en Hostinger)
El proyecto utiliza un enfoque seguro aislando la lógica de negocio del directorio expuesto a internet. 

/
├── app/                  # Controladores, Modelos y Servicios (Motor estadístico e Importador)
├── config/               # Configuración global y conexión PDO
├── storage/              # Archivos temporales, caché y Excel subidos (Permisos 755)
├── vendor/               # Librerías Composer
├── views/                # Vistas y plantillas HTML para el PDF
├── .env                  # Variables de entorno (Credenciales de BD)
└── public_html/          # (O public/) ROOT expuesto a internet.
    ├── index.php         # Front Controller
    └── assets/           # CSS, JS, Imágenes y logotipos.