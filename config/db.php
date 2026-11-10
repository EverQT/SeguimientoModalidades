<?php

/**
 * Conexión a la base de datos del Sistema DTIC (USFX).
 *
 * Entorno local (XAMPP): MySQL corriendo en localhost:3306,
 * usuario root SIN contraseña, base de datos TitulacionDB.
 */

return [
    'class' => \yii\db\Connection::class,
    'dsn' => 'mysql:host=localhost;port=3306;dbname=TitulacionDB',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
