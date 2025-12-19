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
                ['name' => 'Coupe homme', 'price' => '20', 'duration' => 30],
                ['name' => 'Barbe', 'price' => '15', 'duration' => 20],
            ],
            CategoryFixtures::FEMME => [
                ['name' => 'Coupe femme', 'price' => '35', 'duration' => 45],
                ['name' => 'Brushing', 'price' => '25', 'duration' => 30],
            ],
            CategoryFixtures::ENFANT => [
                ['name' => 'Coupe enfant', 'price' => '15', 'duration' => 20],
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