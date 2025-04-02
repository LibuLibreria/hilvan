<?php

namespace App\Service;

use App\Entity\Relacion;
use App\Entity\Persona;
use App\Entity\TipoRelacion;
use App\Repository\RelacionRepository;
use App\Repository\TipoRelacionRepository;
use Doctrine\ORM\EntityManagerInterface;

class RelacionBidireccionalService
{
    private EntityManagerInterface $entityManager;
    private RelacionRepository $relacionRepository;
    private TipoRelacionRepository $tipoRelacionRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RelacionRepository $relacionRepository,
        TipoRelacionRepository $tipoRelacionRepository
    ) {
        $this->entityManager = $entityManager;
        $this->relacionRepository = $relacionRepository;
        $this->tipoRelacionRepository = $tipoRelacionRepository;
    }

    /**
     * Crea una relación bidireccional entre dos personas
     */
    public function crearRelacionBidireccional(Persona $personaOrigen, Persona $personaDestino, TipoRelacion $tipoRelacion): array
    {
        // Crear la relación directa
        $relacion = new Relacion();
        $relacion->setPersonaOrigen($personaOrigen);
        $relacion->setPersonaDestino($personaDestino);
        $relacion->setTipoRelacion($tipoRelacion);
        
        $this->entityManager->persist($relacion);
        
        // Buscar el tipo de relación inversa si existe
        $tipoRelacionInversa = $this->buscarTipoRelacionInversa($tipoRelacion);
        
        // Crear la relación inversa
        $relacionInversa = new Relacion();
        $relacionInversa->setPersonaOrigen($personaDestino);
        $relacionInversa->setPersonaDestino($personaOrigen);
        $relacionInversa->setTipoRelacion($tipoRelacionInversa ?: $tipoRelacion);
        
        $this->entityManager->persist($relacionInversa);
        $this->entityManager->flush();
        
        return [$relacion, $relacionInversa];
    }
    
    /**
     * Actualiza una relación y su inversa
     */
    public function actualizarRelacionBidireccional(Relacion $relacion, TipoRelacion $nuevoTipoRelacion): array
    {
        // Buscar la relación inversa
        $relacionInversa = $this->buscarRelacionInversa($relacion);
        
        // Actualizar la relación directa
        $relacion->setTipoRelacion($nuevoTipoRelacion);
        
        // Buscar el tipo de relación inversa si existe
        $tipoRelacionInversa = $this->buscarTipoRelacionInversa($nuevoTipoRelacion);
        
        // Actualizar la relación inversa si existe
        if ($relacionInversa) {
            $relacionInversa->setTipoRelacion($tipoRelacionInversa ?: $nuevoTipoRelacion);
        } else {
            // Crear la relación inversa si no existe
            $relacionInversa = new Relacion();
            $relacionInversa->setPersonaOrigen($relacion->getPersonaDestino());
            $relacionInversa->setPersonaDestino($relacion->getPersonaOrigen());
            $relacionInversa->setTipoRelacion($tipoRelacionInversa ?: $nuevoTipoRelacion);
            $this->entityManager->persist($relacionInversa);
        }
        
        $this->entityManager->flush();
        
        return [$relacion, $relacionInversa];
    }
    
    /**
     * Elimina una relación y su inversa
     */
    public function eliminarRelacionBidireccional(Relacion $relacion): void
    {
        // Buscar la relación inversa
        $relacionInversa = $this->buscarRelacionInversa($relacion);
        
        // Eliminar la relación directa
        $this->entityManager->remove($relacion);
        
        // Eliminar la relación inversa si existe
        if ($relacionInversa) {
            $this->entityManager->remove($relacionInversa);
        }
        
        $this->entityManager->flush();
    }
    
    /**
     * Busca la relación inversa de una relación dada
     */
    private function buscarRelacionInversa(Relacion $relacion): ?Relacion
    {
        return $this->relacionRepository->findOneBy([
            'personaOrigen' => $relacion->getPersonaDestino(),
            'personaDestino' => $relacion->getPersonaOrigen()
        ]);
    }
    
    /**
     * Busca el tipo de relación inversa para un tipo de relación dado
     * Por ejemplo, si el tipo es "padre", devuelve "hijo"
     */
    private function buscarTipoRelacionInversa(TipoRelacion $tipoRelacion): ?TipoRelacion
    {
        // Buscar por nombre inverso
        // En una implementación real, esto podría ser una relación directa en la entidad
        // o una tabla de mapeo de relaciones inversas
        $tiposRelacion = $this->tipoRelacionRepository->findAll();
        
        foreach ($tiposRelacion as $tipo) {
            if ($tipo->getNombreInverso() === $tipoRelacion->getNombre()) {
                return $tipo;
            }
        }
        
        return null;
    }
}
