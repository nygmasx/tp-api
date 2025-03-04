<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('', name: 'app_user_index', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Retourne la liste des utilisateurs",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: User::class, groups: ["user:read"]))
        )
    )]
    #[OA\Tag(name: "Users")]
    #[Security(name: "Bearer")]
    public function index(UserRepository $userRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->json(
            $userRepository->findAll(),
            Response::HTTP_OK,
            [],
            ['groups' => 'user:read']
        );
    }

    #[Route('', name: 'app_user_create', methods: ['POST'])]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "email", type: "string"),
                new OA\Property(property: "password", type: "string")
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Retourne l'utilisateur créé",
        content: new OA\JsonContent(
            ref: new Model(type: User::class, groups: ["user:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Tag(name: "Users")]
    #[Security(name: "Bearer")]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);

        if (!isset($data['password']) || empty($data['password'])) {
            return $this->json(['message' => 'Le mot de passe est requis'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setRoles(['ROLE_USER']);

        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $data['password'])
        );

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(
            $user,
            Response::HTTP_CREATED,
            [],
            ['groups' => 'user:read']
        );
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Retourne les détails d'un utilisateur",
        content: new OA\JsonContent(
            ref: new Model(type: User::class, groups: ["user:read"])
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Utilisateur non trouvé"
    )]
    #[OA\Tag(name: "Users")]
    #[Security(name: "Bearer")]
    public function show(User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->json(
            $user,
            Response::HTTP_OK,
            [],
            ['groups' => 'user:read']
        );
    }

    #[Route('/{id}', name: 'app_user_update', methods: ['PUT'])]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "email", type: "string"),
                new OA\Property(property: "password", type: "string", nullable: true)
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Retourne l'utilisateur mis à jour",
        content: new OA\JsonContent(
            ref: new Model(type: User::class, groups: ["user:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Response(
        response: 404,
        description: "Utilisateur non trouvé"
    )]
    #[OA\Tag(name: "Users")]
    #[Security(name: "Bearer")]
    public function update(Request $request, User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $data['password'])
            );
        }

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json(
            $user,
            Response::HTTP_OK,
            [],
            ['groups' => 'user:read']
        );
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['DELETE'])]
    #[OA\Response(
        response: 204,
        description: "Utilisateur supprimé"
    )]
    #[OA\Response(
        response: 404,
        description: "Utilisateur non trouvé"
    )]
    #[OA\Tag(name: "Users")]
    #[Security(name: "Bearer")]
    public function delete(User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
