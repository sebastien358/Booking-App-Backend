<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public const CABINET = 'category_cabinet';
    public const VISIO = 'category_visio';

    public function load(ObjectManager $manager): void
    {
        $categories = [
            self::CABINET => [
                'name' => 'Au cabinet',
                'slug' => 'au-cabinet'
            ],
            self::VISIO => [
                'name' => 'En visio',
                'slug' => 'en-visio'
            ],
        ];

        foreach ($categories as $ref => $data) {
            $category = new Category();
            $category->setName($data['name']);
            $category->setSlug($data['slug']);

            $manager->persist($category);

            // référence pour les ServiceFixtures
            $this->addReference($ref, $category);
        }

        $manager->flush();
    }
}