<?php

namespace App\DataFixtures;

use App\Entity\Service;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ServiceFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $services = [
            CategoryFixtures::HOMME => [
                [
                    'name' => 'Séance de gestion du stress',
                    'price' => 50,
                    'duration' => 60,
                ],
                [
                    'name' => 'Accompagnement bien-être',
                    'price' => 60,
                    'duration' => 75,
                ],
            ],

            CategoryFixtures::FEMME => [
                [
                    'name' => 'Accompagnement émotionnel',
                    'price' => 60,
                    'duration' => 75,
                ],
                [
                    'name' => 'Développement personnel',
                    'price' => 70,
                    'duration' => 90,
                ],
            ],

            CategoryFixtures::ENFANT => [
                [
                    'name' => 'Accompagnement junior',
                    'price' => 45,
                    'duration' => 45,
                ],
            ],
        ];

        foreach ($services as $categoryRef => $items) {
            /** @var Category $category */
            $category = $this->getReference($categoryRef, Category::class);

            foreach ($items as $data) {
                $service = new Service();
                $service->setName($data['name']);
                $service->setPrice($data['price']);
                $service->setDuration($data['duration']);
                $service->setCategory($category);

                $manager->persist($service);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }
}