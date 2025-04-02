<?php

namespace App\Service;

use App\Entity\Persona;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class GestorArchivosService
{
    private $targetDirectory;
    private $slugger;

    public function __construct(string $targetDirectory, SluggerInterface $slugger)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
    }

    /**
     * Sube una foto para una persona
     */
    public function subirFoto(UploadedFile $file, Persona $persona): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException $e) {
            throw new \Exception('Error al subir el archivo: ' . $e->getMessage());
        }

        return $fileName;
    }

    /**
     * Elimina una foto existente
     */
    public function eliminarFoto(string $fileName): bool
    {
        $filePath = $this->getTargetDirectory() . '/' . $fileName;
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }

    /**
     * Actualiza la foto de una persona
     */
    public function actualizarFoto(UploadedFile $file, Persona $persona, string $fotoAnterior = null): string
    {
        // Eliminar la foto anterior si existe
        if ($fotoAnterior) {
            $this->eliminarFoto($fotoAnterior);
        }
        
        // Subir la nueva foto
        return $this->subirFoto($file, $persona);
    }

    /**
     * Obtiene la ruta completa de la foto
     */
    public function getRutaFoto(string $fileName): string
    {
        return $this->getTargetDirectory() . '/' . $fileName;
    }

    /**
     * Obtiene la URL pública de la foto
     */
    public function getUrlFoto(string $fileName): string
    {
        // En una implementación real, esto debería devolver la URL pública
        // basada en la configuración del servidor web
        return '/uploads/fotos/' . $fileName;
    }

    /**
     * Obtiene el directorio de destino
     */
    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}
