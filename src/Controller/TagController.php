<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\Persona;
use App\Repository\TagRepository;
use App\Repository\PersonaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tags')]
final class TagController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private TagRepository $tagRepository;
    private PersonaRepository $personaRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        TagRepository $tagRepository,
        PersonaRepository $personaRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->tagRepository = $tagRepository;
        $this->personaRepository = $personaRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('', name: 'app_tag_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $tags = $this->tagRepository->findAll();
        
        return $this->json($tags, Response::HTTP_OK, [], [
            'groups' => ['tag:read']
        ]);
    }

    #[Route('/{id}', name: 'app_tag_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $tag = $this->tagRepository->find($id);
        
        if (!$tag) {
            return $this->json(['message' => 'Tag no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json($tag, Response::HTTP_OK, [], [
            'groups' => ['tag:read', 'tag:details']
        ]);
    }

    #[Route('', name: 'app_tag_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Verificar datos requeridos
        if (!isset($data['nombre'])) {
            return $this->json(['message' => 'Falta el nombre del tag'], Response::HTTP_BAD_REQUEST);
        }
        
        // Crear el tag
        $tag = new Tag();
        $tag->setNombre($data['nombre']);
        $tag->setDescripcion($data['descripcion'] ?? null);
        
        $errors = $this->validator->validate($tag);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->persist($tag);
        $this->entityManager->flush();
        
        return $this->json($tag, Response::HTTP_CREATED, [], [
            'groups' => ['tag:read']
        ]);
    }

    #[Route('/{id}', name: 'app_tag_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $tag = $this->tagRepository->find($id);
        
        if (!$tag) {
            return $this->json(['message' => 'Tag no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['nombre'])) {
            $tag->setNombre($data['nombre']);
        }
        
        if (isset($data['descripcion'])) {
            $tag->setDescripcion($data['descripcion']);
        }
        
        $errors = $this->validator->validate($tag);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->flush();
        
        return $this->json($tag, Response::HTTP_OK, [], [
            'groups' => ['tag:read']
        ]);
    }

    #[Route('/{id}', name: 'app_tag_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $tag = $this->tagRepository->find($id);
        
        if (!$tag) {
            return $this->json(['message' => 'Tag no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($tag);
        $this->entityManager->flush();
        
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/personas', name: 'app_tag_personas', methods: ['GET'])]
    public function getPersonasByTag(int $id): JsonResponse
    {
        $tag = $this->tagRepository->find($id);
        
        if (!$tag) {
            return $this->json(['message' => 'Tag no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        // Aquí se asume que existe una relación ManyToMany entre Tag y Persona
        // y que hay un método getPersonas() en la entidad Tag
        // En caso contrario, se debería implementar una consulta personalizada
        
        return $this->json($tag->getPersonas(), Response::HTTP_OK, [], [
            'groups' => ['persona:read']
        ]);
    }

    #[Route('/persona/{id}/asignar', name: 'app_tag_asignar_persona', methods: ['POST'])]
    public function asignarTagAPersona(int $id, Request $request): JsonResponse
    {
        $persona = $this->personaRepository->find($id);
        
        if (!$persona) {
            return $this->json(['message' => 'Persona no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['tagId'])) {
            return $this->json(['message' => 'Falta el ID del tag'], Response::HTTP_BAD_REQUEST);
        }
        
        $tag = $this->tagRepository->find($data['tagId']);
        
        if (!$tag) {
            return $this->json(['message' => 'Tag no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        // Aquí se asume que existe una relación ManyToMany entre Tag y Persona
        // y que hay métodos addTag() y removeTag() en la entidad Persona
        // En caso contrario, se debería implementar una lógica personalizada
        
        $persona->addTag($tag);
        $this->entityManager->flush();
        
        return $this->json(['message' => 'Tag asignado correctamente'], Response::HTTP_OK);
    }

    #[Route('/persona/{id}/desasignar', name: 'app_tag_desasignar_persona', methods: ['POST'])]
    public function desasignarTagDePersona(int $id, Request $request): JsonResponse
    {
        $persona = $this->personaRepository->find($id);
        
        if (!$persona) {
            return $this->json(['message' => 'Persona no encontrada'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['tagId'])) {
            return $this->json(['message' => 'Falta el ID del tag'], Response::HTTP_BAD_REQUEST);
        }
        
        $tag = $this->tagRepository->find($data['tagId']);
        
        if (!$tag) {
            return $this->json(['message' => 'Tag no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        $persona->removeTag($tag);
        $this->entityManager->flush();
        
        return $this->json(['message' => 'Tag desasignado correctamente'], Response::HTTP_OK);
    }
}
