<?php

namespace App\Controller;

use App\Entity\Persona;
use App\Repository\PersonaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/personas')]
final class PersonaController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private PersonaRepository $personaRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        PersonaRepository $personaRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->personaRepository = $personaRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('', name: 'app_persona_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $personas = $this->personaRepository->findAll();
        
        return $this->json($personas, Response::HTTP_OK, [], [
            'groups' => ['persona:read']
        ]);
    }

    #[Route('/{id}', name: 'app_persona_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $persona = $this->personaRepository->find($id);
        
        if (!$persona) {
            return $this->json(['message' => 'Persona no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json($persona, Response::HTTP_OK, [], [
            'groups' => ['persona:read', 'persona:relations']
        ]);
    }

    #[Route('', name: 'app_persona_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $persona = new Persona();
        $persona->setNombre($data['nombre'] ?? '');
        $persona->setApellido($data['apellido'] ?? '');
        
        if (isset($data['fechaNacimiento'])) {
            $fechaNacimiento = new \DateTime($data['fechaNacimiento']);
            $persona->setFechaNacimiento($fechaNacimiento);
        }
        
        $persona->setObservaciones($data['observaciones'] ?? null);
        $persona->setFoto($data['foto'] ?? null);
        
        $errors = $this->validator->validate($persona);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->persist($persona);
        $this->entityManager->flush();
        
        return $this->json($persona, Response::HTTP_CREATED, [], [
            'groups' => ['persona:read']
        ]);
    }

    #[Route('/{id}', name: 'app_persona_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $persona = $this->personaRepository->find($id);
        
        if (!$persona) {
            return $this->json(['message' => 'Persona no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['nombre'])) {
            $persona->setNombre($data['nombre']);
        }
        
        if (isset($data['apellido'])) {
            $persona->setApellido($data['apellido']);
        }
        
        if (isset($data['fechaNacimiento'])) {
            $fechaNacimiento = new \DateTime($data['fechaNacimiento']);
            $persona->setFechaNacimiento($fechaNacimiento);
        }
        
        if (isset($data['observaciones'])) {
            $persona->setObservaciones($data['observaciones']);
        }
        
        if (isset($data['foto'])) {
            $persona->setFoto($data['foto']);
        }
        
        $errors = $this->validator->validate($persona);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->flush();
        
        return $this->json($persona, Response::HTTP_OK, [], [
            'groups' => ['persona:read']
        ]);
    }

    #[Route('/{id}', name: 'app_persona_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $persona = $this->personaRepository->find($id);
        
        if (!$persona) {
            return $this->json(['message' => 'Persona no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($persona);
        $this->entityManager->flush();
        
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/buscar', name: 'app_persona_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $personas = $this->personaRepository->buscarPersonas($query);
        
        return $this->json($personas, Response::HTTP_OK, [], [
            'groups' => ['persona:read']
        ]);
    }
}
