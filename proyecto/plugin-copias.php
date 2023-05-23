<?php
/*
Plugin Name: Plugin Copias de Seguridad
Plugin URI: https://example.com/
Description: Un complemento de copia de seguridad personalizado para WordPress.
Version: 1.0
Author: Pau
*/

// Establecer opciones default. utilizadas para almacenar la frecuencia, el tipo y la ubicación donde se guardarán las copias de seguridad. 
add_option('backup_frequency', 'daily');
add_option('backup_type', 'full');
add_option('backup_location', '/wp-content/backups');

// Programar copias de seguridad (schedule backups)
// custom_backup_schedule()=  programa las copias según la frecuencia establecida. 
//  |--> Obtiene info get_option('backup_frequency'). 
//Luego, utiliza wp_schedule_event(), programa un evento de copia de seguridad personalizado (custom_backup_event) 
//Dependiendo de la frecuencia, se programa un evento diario, semanal o mensual.
function custom_backup_schedule() {
    $frequency = get_option('backup_frequency');
    if ($frequency == 'daily') {
        wp_schedule_event(time(), 'daily', 'custom_backup_event');
    } elseif ($frequency == 'weekly') {
        wp_schedule_event(time(), 'weekly', 'custom_backup_event');
    } elseif ($frequency == 'monthly') {
        wp_schedule_event(time(), 'monthly', 'custom_backup_event');
    }
}
add_action('admin_init', 'custom_backup_schedule');

// Funcion de la copia de seguridad
//custom_backup()=función principal que se ejecuta cuando ocurre el evento de copia personalizado (custom_backup_event). 
//Recibe info: tipo y ubicación dichas antes.
function custom_backup() {
    //  Tener opciones (tipo y ubicacion) de copia de seguridad
    $type = get_option('backup_type');
    $location = get_option('backup_location');

    // Generar nombre de la copia (dia, mes, año)
    $date = date('Y-m-d');
    $filename = $type . '-' . $date . '.zip';

    // Backup de archivos y base de datos de WordPress 
    if ($type == 'full') {
        // Backup de archivos de WordPress 
        $zip = new ZipArchive();
        $zip->open($location . '/' . $filename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
        $rootPath = realpath(dirname(__FILE__) . '/../');
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();

        // Backup de la base de datos WordPress 
        $backup_file = $location . '/database-' . $date . '.sql';
        exec("mysqldump --user=" . DB_USER . " --password=" . DB_PASSWORD . " --host=" . DB_HOST . " " . DB_NAME . " > " . $backup_file);
        
    } elseif ($type == 'files') {
        // Backup de archivos de WordPress 
        $zip = new ZipArchive();
        $zip->open($location . '/' . $filename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(ABSPATH),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(ABSPATH) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        
    } elseif ($type == 'database') {
        // Backup de base de datos de WordPress 
        $backup_file = $location . '/database-' . $date . '.sql';
        exec("mysqldump --user=" . DB_USER . " --password=" . DB_PASSWORD . " --host=" . DB_HOST . " " . DB_NAME . " > " . $backup_file);
    }
}
add_action('custom_backup_event', 'custom_backup');
