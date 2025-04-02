<?php

namespace App\Controller;

use App\Entity\Observacion;
use App\Entity\Persona;
use App\Repository\ObservacionRepository;
use App\Repository\PersonaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/observaciones')]
final class ObservacionController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ObservacionRepository $observacionRepository;
    private PersonaRepository $personaRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        ObservacionRepository $observacionRepository,
        PersonaRepository $personaRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->observacionRepository = $observacionRepository;
        $this->personaRepository = $personaRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('', name: 'app_observacion_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $observaciones = $this->observacionRepository->findAll();
        
        return $this->json($observaciones, Response::HTTP_OK, [], [
            'groups' => ['observacion:read']
        ]);
    }

    #[Route('/{id}', name: 'app_observacion_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $observacion = $this->observacionRepository->find($id);
        
        if (!$observacion) {
            return $this->json(['message' => 'Observación no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json($observacion, Response::HTTP_OK, [], [
            'groups' => ['observacion:read', 'observacion:details']
        ]);
    }

    #[Route('', name: 'app_observacion_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Verificar datos requeridos
        if (!isset($data['texto']) || !isset($data['personaId'])) {
            return $this->json(['message' => 'Faltan datos requeridos (texto, personaId)'], Response::HTTP_BAD_REQUEST);
        }
        
        // Obtener persona relacionada
        $persona = $this->personaRepository->find($data['personaId']);
        
        if (!$persona) {
            return $this->json(['message' => 'Persona no encontrada'], Response::HTTP_BAD_REQUEST);
        }
        
        // Crear la observación
        $observacion = new Observacion();
        $observacion->setTexto($data['texto']);
        $observacion->setFecha(new \DateTime());
        $observacion->setPersona($persona);
        
        $errors = $this->validator->validate($observacion);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->persist($observacion);
        $this->entityManager->flush();
        
        return $this->json($observacion, Response::HTTP_CREATED, [], [
            'groups' => ['observacion:read']
        ]);
    }

    #[Route('/{id}', name: 'app_observacion_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $observacion = $this->observacionRepository->find($id);
        
        if (!$observacion) {
            return $this->json(['message' => 'Observación no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['texto'])) {
            $observacion->setTexto($data['texto']);
        }
        
        if (isset($data['fecha'])) {
            $observacion->setFecha(new \DateTime($data['fecha']));
        }
        
        if (isset($data['personaId'])) {
            $persona = $this->personaRepository->find($data['personaId']);
            if (!$persona) {
                return $this->json(['message' => 'Persona no encontrada'], Response::HTTP_BAD_REQUEST);
            }
            $observacion->setPersona($persona);
        }
        
        $errors = $this->validator->validate($observacion);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->flush();
        
        return $this->json($observacion, Response::HTTP_OK, [], [
            'groups' => ['observacion:read']
        ]);
    }

    #[Route('/{id}', name: 'app_observacion_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $observacion = $this->observacionRepository->find($id);
        
        if (!$observacion) {
            return $this->json(['message' => 'Observación no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($observacion);
        $this->entityManager->flush();
        
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/persona/{id}', name: 'app_observacion_by_persona', methods: ['GET'])]
    public function getObservacionesByPersona(int $id): JsonResponse
    {
        $persona = $this->personaRepository->find($id);
        
        if (!$persona) {
            return $this->json(['message' => 'Persona no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        $observaciones = $this->observacionRepository->findBy(['persona' => $persona], ['fecha' => 'DESC']);
        
        return $this->json($observaciones, Response::HTTP_OK, [], [
            'groups' => ['observacion:read']
        ]);
    }

    #[Route('/cronologico', name: 'app_observacion_cronologico', methods: ['GET'])]
    public function getObservacionesCronologicas(Request $request): JsonResponse
    {
        $limit = $request->query->get('limit', 20);
        
        // En una implementación real, se debería usar un repositorio personalizado
        // para obtener las observaciones con una consulta SQL optimizada
        $observaciones = $this->observacionRepository->findBy([], ['fecha' => 'DESC'], $limit);
        
        return $this->json($observaciones, Response::HTTP_OK, [], [
            'groups' => ['observacion:read', 'observacion:details']
        ]);
    }
}
