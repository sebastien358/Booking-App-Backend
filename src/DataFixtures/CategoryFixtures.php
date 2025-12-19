<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public const HOMME = 'category_homme';
    public const FEMME = 'category_femme';
    public const ENFANT = 'category_enfant';

    public function load(ObjectManager $manager): void
    {
        $categories = [
            self::HOMME => ['name' => 'Homme', 'slug' => 'homme'],
            self::FEMME => ['name' => 'Femme', 'slug' => 'femme'],
            self::ENFANT => ['name' => 'Enfant', 'slug' => 'enfant'],
        ];

        foreach ($categories as $ref => $data) {
            $category = new Category();
            $category->setName($data['name']);
            $category->setSlug($data['slug']);

            $manager->persist($category);

            // 🔴 LIGNE CRUCIALE
            $this->addReference($ref, $category);
        }

        $manager->flush();
    }
}
