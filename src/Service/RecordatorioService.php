<?php

namespace App\Service;

use App\Entity\FechaRecordatorio;
use App\Entity\Persona;
use App\Repository\FechaRecordatorioRepository;
use Doctrine\ORM\EntityManagerInterface;

class RecordatorioService
{
    private EntityManagerInterface $entityManager;
    private FechaRecordatorioRepository $fechaRecordatorioRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        FechaRecordatorioRepository $fechaRecordatorioRepository
    ) {
        $this->entityManager = $entityManager;
        $this->fechaRecordatorioRepository = $fechaRecordatorioRepository;
    }

    /**
     * Obtiene los próximos recordatorios en un rango de días
     */
    public function obtenerProximosRecordatorios(int $dias = 30): array
    {
        $fechaActual = new \DateTime();
        $fechaLimite = (new \DateTime())->modify("+{$dias} days");
        
        $todasLasFechas = $this->fechaRecordatorioRepository->findAll();
        $fechasProximas = [];
        
        foreach ($todasLasFechas as $fecha) {
            $fechaEvento = clone $fecha->getFecha();
            
            // Si es recurrente, ajustar al año actual
            if ($fecha->isEsRecurrente()) {
                $fechaEvento->setDate(
                    $fechaActual->format('Y'),
                    $fechaEvento->format('m'),
                    $fechaEvento->format('d')
                );
                
                // Si ya pasó este año, usar el próximo año
                if ($fechaEvento < $fechaActual) {
                    $fechaEvento->modify('+1 year');
                }
            }
            
            // Comprobar si está dentro del rango solicitado
            if ($fechaEvento >= $fechaActual && $fechaEvento <= $fechaLimite) {
                $fechasProximas[] = [
                    'id' => $fecha->getId(),
                    'fecha' => $fechaEvento->format('Y-m-d'),
                    'descripcion' => $fecha->getDescripcion(),
                    'esRecurrente' => $fecha->isEsRecurrente(),
                    'persona' => [
                        'id' => $fecha->getPersona()->getId(),
                        'nombre' => $fecha->getPersona()->getNombre(),
                        'apellido' => $fecha->getPersona()->getApellido()
                    ],
                    'diasRestantes' => $this->calcularDiasRestantes($fechaActual, $fechaEvento)
                ];
            }
        }
        
        // Ordenar por fecha
        usort($fechasProximas, function($a, $b) {
            return strtotime($a['fecha']) - strtotime($b['fecha']);
        });
        
        return $fechasProximas;
    }
    
    /**
     * Obtiene los recordatorios para una persona específica
     */
    public function obtenerRecordatoriosPersona(Persona $persona, int $dias = 365): array
    {
        $fechaActual = new \DateTime();
        $fechaLimite = (new \DateTime())->modify("+{$dias} days");
        
        $fechasPersona = $this->fechaRecordatorioRepository->findBy(['persona' => $persona]);
        $fechasProximas = [];
        
        foreach ($fechasPersona as $fecha) {
            $fechaEvento = clone $fecha->getFecha();
            
            // Si es recurrente, ajustar al año actual
            if ($fecha->isEsRecurrente()) {
                $fechaEvento->setDate(
                    $fechaActual->format('Y'),
                    $fechaEvento->format('m'),
                    $fechaEvento->format('d')
                );
                
                // Si ya pasó este año, usar el próximo año
                if ($fechaEvento < $fechaActual) {
                    $fechaEvento->modify('+1 year');
                }
            }
            
            // Comprobar si está dentro del rango solicitado
            if ($fechaEvento >= $fechaActual && $fechaEvento <= $fechaLimite) {
                $fechasProximas[] = [
                    'id' => $fecha->getId(),
                    'fecha' => $fechaEvento->format('Y-m-d'),
                    'descripcion' => $fecha->getDescripcion(),
                    'esRecurrente' => $fecha->isEsRecurrente(),
                    'diasRestantes' => $this->calcularDiasRestantes($fechaActual, $fechaEvento)
                ];
            }
        }
        
        // Ordenar por fecha
        usort($fechasProximas, function($a, $b) {
            return strtotime($a['fecha']) - strtotime($b['fecha']);
        });
        
        return $fechasProximas;
    }
    
    /**
     * Calcula los días restantes entre dos fechas
     */
    private function calcularDiasRestantes(\DateTime $fechaActual, \DateTime $fechaEvento): int
    {
        $intervalo = $fechaActual->diff($fechaEvento);
        return $intervalo->days;
    }
    
    /**
     * Crea un nuevo recordatorio
     */
    public function crearRecordatorio(Persona $persona, \DateTime $fecha, string $descripcion, bool $esRecurrente): FechaRecordatorio
    {
        $recordatorio = new FechaRecordatorio();
        $recordatorio->setPersona($persona);
        $recordatorio->setFecha($fecha);
        $recordatorio->setDescripcion($descripcion);
        $recordatorio->setEsRecurrente($esRecurrente);
        
        $this->entityManager->persist($recordatorio);
        $this->entityManager->flush();
        
        return $recordatorio;
    }
    
    /**
     * Actualiza un recordatorio existente
     */
    public function actualizarRecordatorio(FechaRecordatorio $recordatorio, array $datos): FechaRecordatorio
    {
        if (isset($datos['fecha'])) {
            $recordatorio->setFecha(new \DateTime($datos['fecha']));
        }
        
        if (isset($datos['descripcion'])) {
            $recordatorio->setDescripcion($datos['descripcion']);
        }
        
        if (isset($datos['esRecurrente'])) {
            $recordatorio->setEsRecurrente($datos['esRecurrente']);
        }
        
        $this->entityManager->flush();
        
        return $recordatorio;
    }
    
    /**
     * Elimina un recordatorio
     */
    public function eliminarRecordatorio(FechaRecordatorio $recordatorio): void
    {
        $this->entityManager->remove($recordatorio);
        $this->entityManager->flush();
    }
}
