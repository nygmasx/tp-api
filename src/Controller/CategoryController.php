<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private TagAwareCacheInterface $cache
    ) {
    }

    #[Route('', name: 'app_category_index', methods: ['GET'])]
    #[OA\Parameter(
        name: "page",
        description: "Numéro de page",
        in: "query",
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Parameter(
        name: "limit",
        description: "Nombre d'éléments par page",
        in: "query",
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Retourne la liste des catégories",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: Category::class, groups: ["category:read"]))
        )
    )]
    #[OA\Tag(name: "Categories")]
    public function index(Request $request, CategoryRepository $categoryRepository): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $cacheKey = "categories_" . $page . "_" . $limit;

        $categories = $this->cache->get($cacheKey, function (ItemInterface $item) use ($categoryRepository, $page, $limit) {
            $item->expiresAfter(3600);
            $item->tag(['categoriesCache']);

            $offset = ($page - 1) * $limit;
            return $categoryRepository->findBy([], [], $limit, $offset);
        });

        return $this->json(
            $categories,
            Response::HTTP_OK,
            [],
            ['groups' => 'category:read']
        );
    }

    #[Route('', name: 'app_category_create', methods: ['POST'])]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            ref: new Model(type: Category::class, groups: ["category:write"])
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Retourne la catégorie créée",
        content: new OA\JsonContent(
            ref: new Model(type: Category::class, groups: ["category:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Tag(name: "Categories")]
    #[Security(name: "Bearer")]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $category = $this->serializer->deserialize(
            $request->getContent(),
            Category::class,
            'json',
            ['groups' => 'category:write']
        );

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $this->cache->invalidateTags(['categoriesCache']);

        return $this->json(
            $category,
            Response::HTTP_CREATED,
            [],
            ['groups' => 'category:read']
        );
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Retourne les détails d'une catégorie",
        content: new OA\JsonContent(
            ref: new Model(type: Category::class, groups: ["category:read"])
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Catégorie non trouvée"
    )]
    #[OA\Tag(name: "Categories")]
    public function show(Category $category): JsonResponse
    {
        return $this->json(
            $category,
            Response::HTTP_OK,
            [],
            ['groups' => 'category:read']
        );
    }

    #[Route('/{id}', name: 'app_category_update', methods: ['PUT'])]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            ref: new Model(type: Category::class, groups: ["category:write"])
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Retourne la catégorie mise à jour",
        content: new OA\JsonContent(
            ref: new Model(type: Category::class, groups: ["category:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Response(
        response: 404,
        description: "Catégorie non trouvée"
    )]
    #[OA\Tag(name: "Categories")]
    #[Security(name: "Bearer")]
    public function update(Request $request, Category $category): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $this->serializer->deserialize(
            $request->getContent(),
            Category::class,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $category,
                'groups' => 'category:write'
            ]
        );

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $this->cache->invalidateTags(['categoriesCache']);

        return $this->json(
            $category,
            Response::HTTP_OK,
            [],
            ['groups' => 'category:read']
        );
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['DELETE'])]
    #[OA\Response(
        response: 204,
        description: "Catégorie supprimée"
    )]
    #[OA\Response(
        response: 404,
        description: "Catégorie non trouvée"
    )]
    #[OA\Tag(name: "Categories")]
    #[Security(name: "Bearer")]
    public function delete(Category $category): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        $this->cache->invalidateTags(['categoriesCache']);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
