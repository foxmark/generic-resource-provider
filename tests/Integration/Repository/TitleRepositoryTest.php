<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Title;
use App\Repository\TitleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TitleRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private TitleRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->repository = static::getContainer()->get(TitleRepository::class);
        $this->em->createQuery('DELETE FROM App\Entity\Title t')->execute();
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Title t')->execute();
        parent::tearDown();
    }

    private function createTitle(string $titleName, string $director = 'Test Director', int $releaseYear = 2000): Title
    {
        $title = new Title();
        $title->setTitle($titleName);
        $title->setDirector($director);
        $title->setReleaseYear($releaseYear);
        $this->em->persist($title);
        $this->em->flush();
        return $title;
    }

    public function testGetDefaultOrderReturnsTitleAsc(): void
    {
        $order = $this->repository->getDefaultOrder();

        $this->assertSame(['title' => 'ASC'], $order);
    }

    public function testTitlesAreReturnedInTitleAscOrder(): void
    {
        $this->createTitle('Zebra Movie');
        $this->createTitle('Alpha Movie');
        $this->createTitle('Middle Movie');

        $results = $this->repository->findAll();
        usort($results, fn(Title $a, Title $b) => strcmp($a->getTitle(), $b->getTitle()));

        $this->assertCount(3, $results);
        $this->assertSame('Alpha Movie', $results[0]->getTitle());
        $this->assertSame('Middle Movie', $results[1]->getTitle());
        $this->assertSame('Zebra Movie', $results[2]->getTitle());
    }

    public function testDefaultOrderIsAppliedByRepository(): void
    {
        $this->createTitle('Zebra Movie');
        $this->createTitle('Alpha Movie');

        $defaultOrder = $this->repository->getDefaultOrder();

        $this->assertArrayHasKey('title', $defaultOrder);
        $this->assertSame('ASC', $defaultOrder['title']);
    }
}
