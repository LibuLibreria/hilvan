<?php

namespace App\Controller;

use App\Entity\TipoRelacion;
use App\Repository\TipoRelacionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tipos-relacion')]
final class TipoRelacionController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private TipoRelacionRepository $tipoRelacionRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        TipoRelacionRepository $tipoRelacionRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->tipoRelacionRepository = $tipoRelacionRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    #[Route('', name: 'app_tipo_relacion_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $tiposRelacion = $this->tipoRelacionRepository->findAll();
        
        return $this->json($tiposRelacion, Response::HTTP_OK, [], [
            'groups' => ['tipo_relacion:read']
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_relacion_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $tipoRelacion = $this->tipoRelacionRepository->find($id);
        
        if (!$tipoRelacion) {
            return $this->json(['message' => 'Tipo de relación no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json($tipoRelacion, Response::HTTP_OK, [], [
            'groups' => ['tipo_relacion:read']
        ]);
    }

    #[Route('', name: 'app_tipo_relacion_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $tipoRelacion = new TipoRelacion();
        $tipoRelacion->setNombre($data['nombre'] ?? '');
        $tipoRelacion->setNombreInverso($data['nombreInverso'] ?? '');
        
        $errors = $this->validator->validate($tipoRelacion);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->persist($tipoRelacion);
        $this->entityManager->flush();
        
        return $this->json($tipoRelacion, Response::HTTP_CREATED, [], [
            'groups' => ['tipo_relacion:read']
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_relacion_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $tipoRelacion = $this->tipoRelacionRepository->find($id);
        
        if (!$tipoRelacion) {
            return $this->json(['message' => 'Tipo de relación no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['nombre'])) {
            $tipoRelacion->setNombre($data['nombre']);
        }
        
        if (isset($data['nombreInverso'])) {
            $tipoRelacion->setNombreInverso($data['nombreInverso']);
        }
        
        $errors = $this->validator->validate($tipoRelacion);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->flush();
        
        return $this->json($tipoRelacion, Response::HTTP_OK, [], [
            'groups' => ['tipo_relacion:read']
        ]);
    }

    #[Route('/{id}', name: 'app_tipo_relacion_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $tipoRelacion = $this->tipoRelacionRepository->find($id);
        
        if (!$tipoRelacion) {
            return $this->json(['message' => 'Tipo de relación no encontrado'], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($tipoRelacion);
        $this->entityManager->flush();
        
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
