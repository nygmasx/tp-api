<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Editor;
use App\Entity\User;
use App\Entity\VideoGame;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Créer l'utilisateur admin
        $admin = new User();
        $admin->setEmail('admin@example.com')
            ->setRoles(['ROLE_ADMIN']);

        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'password')
        );

        $manager->persist($admin);

        // Créer l'utilisateur simple
        $user = new User();
        $user->setEmail('user@example.com')
            ->setRoles(['ROLE_USER']);

        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'password')
        );

        $manager->persist($user);

        // Créer des catégories
        $categories = [];
        $categoryNames = ['Action', 'Adventure', 'RPG', 'Strategy', 'Sports', 'Simulation', 'Puzzle'];

        foreach ($categoryNames as $name) {
            $category = new Category();
            $category->setName($name);
            $manager->persist($category);
            $categories[] = $category;
        }

        // Créer des éditeurs
        $editors = [];
        $editorData = [
            ['Electronic Arts', 'US'],
            ['Ubisoft', 'FR'],
            ['Nintendo', 'JP'],
            ['Activision Blizzard', 'US'],
            ['Square Enix', 'JP'],
            ['CD Projekt', 'PL'],
            ['Rockstar Games', 'US']
        ];

        foreach ($editorData as [$name, $country]) {
            $editor = new Editor();
            $editor->setName($name)
                ->setCountry($country);
            $manager->persist($editor);
            $editors[] = $editor;
        }

        // Créer des jeux vidéo
        $gameData = [
            ['The Legend of Zelda: Breath of the Wild', new \DateTime('2017-03-03'), 'Open-world action-adventure game', 1, 2],
            ['FIFA 23', new \DateTime('2022-09-30'), 'Football simulation game', 4, 0],
            ['Assassin\'s Creed Valhalla', new \DateTime('2020-11-10'), 'Action role-playing game', 1, 1],
            ['Red Dead Redemption 2', new \DateTime('2018-10-26'), 'Action-adventure game', 1, 6],
            ['The Witcher 3: Wild Hunt', new \DateTime('2015-05-19'), 'Action role-playing game', 2, 5],
            ['Super Mario Odyssey', new \DateTime('2017-10-27'), 'Platform game', 1, 2],
            ['Cyberpunk 2077', new \DateTime('2020-12-10'), 'Action role-playing game', 2, 5],
            ['Overwatch', new \DateTime('2016-05-24'), 'Team-based first-person shooter', 0, 3],
            ['Minecraft', new \DateTime('2011-11-18'), 'Sandbox and survival game', 5, 0],
            ['Final Fantasy VII Remake', new \DateTime('2020-04-10'), 'Action role-playing game', 2, 4]
        ];

        foreach ($gameData as [$title, $releaseDate, $description, $categoryIndex, $editorIndex]) {
            $game = new VideoGame();
            $game->setTitle($title)
                ->setReleaseDate($releaseDate)
                ->setDescription($description)
                ->setCategory($categories[$categoryIndex])
                ->setEditor($editors[$editorIndex]);
            $manager->persist($game);
        }

        $manager->flush();
    }
}
