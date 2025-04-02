<?php

namespace App\Controller;

use App\Entity\Relacion;
use App\Entity\Persona;
use App\Entity\TipoRelacion;
use App\Repository\RelacionRepository;
use App\Repository\PersonaRepository;
use App\Repository\TipoRelacionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/relaciones')]
final class RelacionController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private RelacionRepository $relacionRepository;
    private PersonaRepository $personaRepository;
    private TipoRelacionRepository $tipoRelacionRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        RelacionRepository $relacionRepository,
        PersonaRepository $personaRepository,
        TipoRelacionRepository $tipoRelacionRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->relacionRepository = $relacionRepository;
        $this->personaRepository = $personaRepository;
        $this->tipoRelacionRepository = $tipoRelacionRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('', name: 'app_relacion_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $relaciones = $this->relacionRepository->findAll();
        
        return $this->json($relaciones, Response::HTTP_OK, [], [
            'groups' => ['relacion:read']
        ]);
    }

    #[Route('/{id}', name: 'app_relacion_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $relacion = $this->relacionRepository->find($id);
        
        if (!$relacion) {
            return $this->json(['message' => 'Relación no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json($relacion, Response::HTTP_OK, [], [
            'groups' => ['relacion:read', 'relacion:details']
        ]);
    }

    #[Route('', name: 'app_relacion_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Verificar datos requeridos
        if (!isset($data['personaOrigenId']) || !isset($data['personaDestinoId']) || !isset($data['tipoRelacionId'])) {
            return $this->json(['message' => 'Faltan datos requeridos (personaOrigenId, personaDestinoId, tipoRelacionId)'], Response::HTTP_BAD_REQUEST);
        }
        
        // Obtener entidades relacionadas
        $personaOrigen = $this->personaRepository->find($data['personaOrigenId']);
        $personaDestino = $this->personaRepository->find($data['personaDestinoId']);
        $tipoRelacion = $this->tipoRelacionRepository->find($data['tipoRelacionId']);
        
        if (!$personaOrigen || !$personaDestino || !$tipoRelacion) {
            return $this->json(['message' => 'Una o más entidades relacionadas no existen'], Response::HTTP_BAD_REQUEST);
        }
        
        // Crear la relación
        $relacion = new Relacion();
        $relacion->setPersonaOrigen($personaOrigen);
        $relacion->setPersonaDestino($personaDestino);
        $relacion->setTipoRelacion($tipoRelacion);
        
        $errors = $this->validator->validate($relacion);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->persist($relacion);
        
        // Crear la relación inversa automáticamente
        $relacionInversa = new Relacion();
        $relacionInversa->setPersonaOrigen($personaDestino);
        $relacionInversa->setPersonaDestino($personaOrigen);
        
        // Buscar si existe un tipo de relación inversa
        // Si no existe, usar el mismo tipo de relación
        // En una implementación real, se debería tener una tabla de mapeo de relaciones inversas
        $relacionInversa->setTipoRelacion($tipoRelacion);
        
        $this->entityManager->persist($relacionInversa);
        $this->entityManager->flush();
        
        return $this->json($relacion, Response::HTTP_CREATED, [], [
            'groups' => ['relacion:read']
        ]);
    }

    #[Route('/{id}', name: 'app_relacion_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $relacion = $this->relacionRepository->find($id);
        
        if (!$relacion) {
            return $this->json(['message' => 'Relación no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        // Actualizar tipo de relación si se proporciona
        if (isset($data['tipoRelacionId'])) {
            $tipoRelacion = $this->tipoRelacionRepository->find($data['tipoRelacionId']);
            if (!$tipoRelacion) {
                return $this->json(['message' => 'Tipo de relación no encontrado'], Response::HTTP_BAD_REQUEST);
            }
            $relacion->setTipoRelacion($tipoRelacion);
        }
        
        $errors = $this->validator->validate($relacion);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->flush();
        
        return $this->json($relacion, Response::HTTP_OK, [], [
            'groups' => ['relacion:read']
        ]);
    }

    #[Route('/{id}', name: 'app_relacion_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $relacion = $this->relacionRepository->find($id);
        
        if (!$relacion) {
            return $this->json(['message' => 'Relación no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($relacion);
        $this->entityManager->flush();
        
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/persona/{id}', name: 'app_relacion_by_persona', methods: ['GET'])]
    public function getRelacionesByPersona(int $id): JsonResponse
    {
        $persona = $this->personaRepository->find($id);
        
        if (!$persona) {
            return $this->json(['message' => 'Persona no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        $relacionesOrigen = $this->relacionRepository->findBy(['personaOrigen' => $persona]);
        
        return $this->json($relacionesOrigen, Response::HTTP_OK, [], [
            'groups' => ['relacion:read', 'relacion:details']
        ]);
    }
}
