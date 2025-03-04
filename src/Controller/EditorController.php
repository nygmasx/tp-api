<?php

namespace App\Controller;

use App\Entity\Editor;
use App\Repository\EditorRepository;
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

#[Route('/api/editors')]
class EditorController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private TagAwareCacheInterface $cache
    ) {
    }

    #[Route('', name: 'app_editor_index', methods: ['GET'])]
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
        description: "Retourne la liste des éditeurs",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: Editor::class, groups: ["editor:read"]))
        )
    )]
    #[OA\Tag(name: "Editors")]
    public function index(Request $request, EditorRepository $editorRepository): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $cacheKey = "editors_" . $page . "_" . $limit;

        $editors = $this->cache->get($cacheKey, function (ItemInterface $item) use ($editorRepository, $page, $limit) {
            $item->expiresAfter(3600);
            $item->tag(['editorsCache']);

            $offset = ($page - 1) * $limit;
            return $editorRepository->findBy([], [], $limit, $offset);
        });

        return $this->json(
            $editors,
            Response::HTTP_OK,
            [],
            ['groups' => 'editor:read']
        );
    }

    #[Route('', name: 'app_editor_create', methods: ['POST'])]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            ref: new Model(type: Editor::class, groups: ["editor:write"])
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Retourne l'éditeur créé",
        content: new OA\JsonContent(
            ref: new Model(type: Editor::class, groups: ["editor:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Tag(name: "Editors")]
    #[Security(name: "Bearer")]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $editor = $this->serializer->deserialize(
            $request->getContent(),
            Editor::class,
            'json',
            ['groups' => 'editor:write']
        );

        $errors = $this->validator->validate($editor);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($editor);
        $this->entityManager->flush();

        $this->cache->invalidateTags(['editorsCache']);

        return $this->json(
            $editor,
            Response::HTTP_CREATED,
            [],
            ['groups' => 'editor:read']
        );
    }

    #[Route('/{id}', name: 'app_editor_show', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Retourne les détails d'un éditeur",
        content: new OA\JsonContent(
            ref: new Model(type: Editor::class, groups: ["editor:read"])
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Éditeur non trouvé"
    )]
    #[OA\Tag(name: "Editors")]
    public function show(Editor $editor): JsonResponse
    {
        return $this->json(
            $editor,
            Response::HTTP_OK,
            [],
            ['groups' => 'editor:read']
        );
    }

    #[Route('/{id}', name: 'app_editor_update', methods: ['PUT'])]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            ref: new Model(type: Editor::class, groups: ["editor:write"])
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Retourne l'éditeur mis à jour",
        content: new OA\JsonContent(
            ref: new Model(type: Editor::class, groups: ["editor:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Response(
        response: 404,
        description: "Éditeur non trouvé"
    )]
    #[OA\Tag(name: "Editors")]
    #[Security(name: "Bearer")]
    public function update(Request $request, Editor $editor): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $this->serializer->deserialize(
            $request->getContent(),
            Editor::class,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $editor,
                'groups' => 'editor:write'
            ]
        );

        $errors = $this->validator->validate($editor);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $this->cache->invalidateTags(['editorsCache']);

        return $this->json(
            $editor,
            Response::HTTP_OK,
            [],
            ['groups' => 'editor:read']
        );
    }

    #[Route('/{id}', name: 'app_editor_delete', methods: ['DELETE'])]
    #[OA\Response(
        response: 204,
        description: "Éditeur supprimé"
    )]
    #[OA\Response(
        response: 404,
        description: "Éditeur non trouvé"
    )]
    #[OA\Tag(name: "Editors")]
    #[Security(name: "Bearer")]
    public function delete(Editor $editor): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $this->entityManager->remove($editor);
        $this->entityManager->flush();

        $this->cache->invalidateTags(['editorsCache']);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
