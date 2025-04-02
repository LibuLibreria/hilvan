<?php

namespace App\Controller;

use App\Entity\FechaRecordatorio;
use App\Entity\Persona;
use App\Repository\FechaRecordatorioRepository;
use App\Repository\PersonaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/fechas-recordatorio')]
final class FechaRecordatorioController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private FechaRecordatorioRepository $fechaRecordatorioRepository;
    private PersonaRepository $personaRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        FechaRecordatorioRepository $fechaRecordatorioRepository,
        PersonaRepository $personaRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->fechaRecordatorioRepository = $fechaRecordatorioRepository;
        $this->personaRepository = $personaRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('', name: 'app_fecha_recordatorio_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $fechasRecordatorio = $this->fechaRecordatorioRepository->findAll();
        
        return $this->json($fechasRecordatorio, Response::HTTP_OK, [], [
            'groups' => ['fecha_recordatorio:read']
        ]);
    }

    #[Route('/{id}', name: 'app_fecha_recordatorio_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $fechaRecordatorio = $this->fechaRecordatorioRepository->find($id);
        
        if (!$fechaRecordatorio) {
            return $this->json(['message' => 'Fecha recordatorio no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json($fechaRecordatorio, Response::HTTP_OK, [], [
            'groups' => ['fecha_recordatorio:read', 'fecha_recordatorio:details']
        ]);
    }

    #[Route('', name: 'app_fecha_recordatorio_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Verificar datos requeridos
        if (!isset($data['fecha']) || !isset($data['personaId'])) {
            return $this->json(['message' => 'Faltan datos requeridos (fecha, personaId)'], Response::HTTP_BAD_REQUEST);
        }
        
        // Obtener persona relacionada
        $persona = $this->personaRepository->find($data['personaId']);
        
        if (!$persona) {
            return $this->json(['message' => 'Persona no encontrada'], Response::HTTP_BAD_REQUEST);
        }
        
        // Crear la fecha recordatorio
        $fechaRecordatorio = new FechaRecordatorio();
        $fechaRecordatorio->setFecha(new \DateTime($data['fecha']));
        $fechaRecordatorio->setDescripcion($data['descripcion'] ?? '');
        $fechaRecordatorio->setEsRecurrente($data['esRecurrente'] ?? false);
        $fechaRecordatorio->setPersona($persona);
        
        $errors = $this->validator->validate($fechaRecordatorio);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->persist($fechaRecordatorio);
        $this->entityManager->flush();
        
        return $this->json($fechaRecordatorio, Response::HTTP_CREATED, [], [
            'groups' => ['fecha_recordatorio:read']
        ]);
    }

    #[Route('/{id}', name: 'app_fecha_recordatorio_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $fechaRecordatorio = $this->fechaRecordatorioRepository->find($id);
        
        if (!$fechaRecordatorio) {
            return $this->json(['message' => 'Fecha recordatorio no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['fecha'])) {
            $fechaRecordatorio->setFecha(new \DateTime($data['fecha']));
        }
        
        if (isset($data['descripcion'])) {
            $fechaRecordatorio->setDescripcion($data['descripcion']);
        }
        
        if (isset($data['esRecurrente'])) {
            $fechaRecordatorio->setEsRecurrente($data['esRecurrente']);
        }
        
        if (isset($data['personaId'])) {
            $persona = $this->personaRepository->find($data['personaId']);
            if (!$persona) {
                return $this->json(['message' => 'Persona no encontrada'], Response::HTTP_BAD_REQUEST);
            }
            $fechaRecordatorio->setPersona($persona);
        }
        
        $errors = $this->validator->validate($fechaRecordatorio);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->flush();
        
        return $this->json($fechaRecordatorio, Response::HTTP_OK, [], [
            'groups' => ['fecha_recordatorio:read']
        ]);
    }

    #[Route('/{id}', name: 'app_fecha_recordatorio_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $fechaRecordatorio = $this->fechaRecordatorioRepository->find($id);
        
        if (!$fechaRecordatorio) {
            return $this->json(['message' => 'Fecha recordatorio no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($fechaRecordatorio);
        $this->entityManager->flush();
        
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/persona/{id}', name: 'app_fecha_recordatorio_by_persona', methods: ['GET'])]
    public function getFechasByPersona(int $id): JsonResponse
    {
        $persona = $this->personaRepository->find($id);
        
        if (!$persona) {
            return $this->json(['message' => 'Persona no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        $fechasRecordatorio = $this->fechaRecordatorioRepository->findBy(['persona' => $persona]);
        
        return $this->json($fechasRecordatorio, Response::HTTP_OK, [], [
            'groups' => ['fecha_recordatorio:read']
        ]);
    }

    #[Route('/proximos', name: 'app_fecha_recordatorio_proximos', methods: ['GET'])]
    public function getProximasFechas(Request $request): JsonResponse
    {
        $dias = $request->query->get('dias', 30);
        $fechaActual = new \DateTime();
        $fechaLimite = (new \DateTime())->modify("+{$dias} days");
        
        // En una implementación real, se debería usar un repositorio personalizado
        // para obtener las fechas próximas con una consulta SQL optimizada
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
                    ]
                ];
            }
        }
        
        // Ordenar por fecha
        usort($fechasProximas, function($a, $b) {
            return strtotime($a['fecha']) - strtotime($b['fecha']);
        });
        
        return $this->json($fechasProximas, Response::HTTP_OK);
    }
}
