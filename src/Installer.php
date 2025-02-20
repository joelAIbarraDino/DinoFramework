<?php

namespace DinoEngine;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use Composer\Script\Event;

class Installer{

    public static function postInstall(Event $event): void{

        $composer = $event->getComposer();
        $packageName = 'joel/dino-framework'; // Cambia esto por el nombre real de tu paquete

        // Verifica si el paquete está en el proyecto principal o si es un requerimiento
        $isRoot = $composer->getPackage()->getName() === $packageName;

        if ($isRoot) {
            echo "El paquete está en desarrollo, no ejecutando el script.\n";
            return;
        }

        echo "Ejecutando script postInstall para una instalación externa...\n";


        $filesystem = new Filesystem();

        // Obtén la ruta base del proyecto (donde se está instalando el framework)
        $baseDir = getcwd(); // Obtiene el directorio actual de trabajo (raíz del proyecto)

        if($filesystem == __DIR__)
            return; 

        // Estructura de carpetas a crear
        $directories = [
            'app/Middlewares',
            'app/Controllers',
            'app/Models',
            'app/Views',
            'app/Views/pages',
            'public',
        ];

        // Crear las carpetas
        foreach ($directories as $directory) {
            $fullPath = $baseDir . '/' . $directory;

            if (!$filesystem->exists($fullPath)) {
                try {
                    $filesystem->mkdir($fullPath, 0755); // Crea la carpeta con permisos 0755
                    echo "Carpeta creada: $fullPath\n";
                } catch (IOExceptionInterface $e) {
                    echo "Error al crear la carpeta: " . $e->getMessage() . "\n";
                }
            }
        }

        // Copiar archivos de configuración
        $filesToCopy = [
            // Archivos desde el núcleo del framework
            __DIR__ . '/FilesExamples/.env.example' => $baseDir . '/public/.env',
            __DIR__ . '/FilesExamples/index.php' => $baseDir . '/public/index.php',
            __DIR__ . '/FilesExamples/User.php' => $baseDir . '/app/Models/User.php',
            __DIR__ . '/FilesExamples/HomeController.php' => $baseDir . '/app/Controllers/HomeController.php',
            __DIR__ . '/FilesExamples/indexExample.php' => $baseDir . '/app/Views/pages/indexExample.php',
        ];

        foreach ($filesToCopy as $source => $destination) {
            if (!$filesystem->exists($destination)) {
                try {
                    $filesystem->copy($source, $destination);
                    echo "Archivo copiado: $destination\n";
                } catch (IOExceptionInterface $e) {
                    echo "Error al copiar el archivo: " . $e->getMessage() . "\n";
                }
            } else {
                echo "El archivo ya existe: $destination\n";
            }
        }

        echo "¡Estructura de proyecto creada con éxito!\n";
    }
}