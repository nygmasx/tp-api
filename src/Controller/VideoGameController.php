<?php

namespace App\Controller;

use App\Entity\VideoGame;
use App\Repository\VideoGameRepository;
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

#[Route('/api/video-games')]
class VideoGameController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private TagAwareCacheInterface $cache
    ) {
    }

    #[Route('', name: 'app_video_game_index', methods: ['GET'])]
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
        description: "Retourne la liste des jeux vidéo",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(ref: new Model(type: VideoGame::class, groups: ["game:read"]))
        )
    )]
    #[OA\Tag(name: "Video Games")]
    public function index(Request $request, VideoGameRepository $videoGameRepository): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $cacheKey = "video_games_" . $page . "_" . $limit;

        $videoGames = $this->cache->get($cacheKey, function (ItemInterface $item) use ($videoGameRepository, $page, $limit) {
            $item->expiresAfter(3600);
            $item->tag(['videoGamesCache']);

            $offset = ($page - 1) * $limit;
            return $videoGameRepository->findBy([], [], $limit, $offset);
        });

        return $this->json(
            $videoGames,
            Response::HTTP_OK,
            [],
            ['groups' => 'game:read']
        );
    }

    #[Route('', name: 'app_video_game_create', methods: ['POST'])]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            ref: new Model(type: VideoGame::class, groups: ["game:write"])
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Retourne le jeu vidéo créé",
        content: new OA\JsonContent(
            ref: new Model(type: VideoGame::class, groups: ["game:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Tag(name: "Video Games")]
    #[Security(name: "Bearer")]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $videoGame = $this->serializer->deserialize(
            $request->getContent(),
            VideoGame::class,
            'json',
            ['groups' => 'game:write']
        );

        $errors = $this->validator->validate($videoGame);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($videoGame);
        $this->entityManager->flush();

        $this->cache->invalidateTags(['videoGamesCache']);

        return $this->json(
            $videoGame,
            Response::HTTP_CREATED,
            [],
            ['groups' => 'game:read']
        );
    }

    #[Route('/{id}', name: 'app_video_game_show', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: "Retourne les détails d'un jeu vidéo",
        content: new OA\JsonContent(
            ref: new Model(type: VideoGame::class, groups: ["game:read"])
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Jeu vidéo non trouvé"
    )]
    #[OA\Tag(name: "Video Games")]
    public function show(VideoGame $videoGame): JsonResponse
    {
        return $this->json(
            $videoGame,
            Response::HTTP_OK,
            [],
            ['groups' => 'game:read']
        );
    }

    #[Route('/{id}', name: 'app_video_game_update', methods: ['PUT'])]
    #[OA\RequestBody(
        content: new OA\JsonContent(
            ref: new Model(type: VideoGame::class, groups: ["game:write"])
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Retourne le jeu vidéo mis à jour",
        content: new OA\JsonContent(
            ref: new Model(type: VideoGame::class, groups: ["game:read"])
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Données invalides"
    )]
    #[OA\Response(
        response: 404,
        description: "Jeu vidéo non trouvé"
    )]
    #[OA\Tag(name: "Video Games")]
    #[Security(name: "Bearer")]
    public function update(Request $request, VideoGame $videoGame): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $this->serializer->deserialize(
            $request->getContent(),
            VideoGame::class,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $videoGame,
                'groups' => 'game:write'
            ]
        );

        $errors = $this->validator->validate($videoGame);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $this->cache->invalidateTags(['videoGamesCache']);

        return $this->json(
            $videoGame,
            Response::HTTP_OK,
            [],
            ['groups' => 'game:read']
        );
    }

    #[Route('/{id}', name: 'app_video_game_delete', methods: ['DELETE'])]
    #[OA\Response(
        response: 204,
        description: "Jeu vidéo supprimé"
    )]
    #[OA\Response(
        response: 404,
        description: "Jeu vidéo non trouvé"
    )]
    #[OA\Tag(name: "Video Games")]
    #[Security(name: "Bearer")]
    public function delete(VideoGame $videoGame): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $this->entityManager->remove($videoGame);
        $this->entityManager->flush();

        $this->cache->invalidateTags(['videoGamesCache']);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
