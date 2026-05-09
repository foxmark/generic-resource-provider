<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Title;
use Doctrine\ORM\EntityManagerInterface;

class TitleApiTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    private Client $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->em->createQuery('DELETE FROM App\Entity\Title t')->execute();
    }

    private function createTitle(array $override = []): Title
    {
        $title = new Title();
        $title->setTitle($override['title'] ?? 'Test Movie');
        $title->setDirector($override['director'] ?? 'Test Director');
        $title->setReleaseYear($override['releaseYear'] ?? 2000);
        if (isset($override['durationMinutes'])) {
            $title->setDurationMinutes($override['durationMinutes']);
        }
        $this->em->persist($title);
        $this->em->flush();
        return $title;
    }

    // --- Endpoint definition tests ---

    public function testGetCollectionEndpointIsDefined(): void
    {
        $this->client->request('GET', '/api/titles');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testGetSingleTitleEndpointIsDefined(): void
    {
        $title = $this->createTitle();

        $this->client->request('GET', '/api/titles/' . $title->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testGetNonExistentTitleReturns404(): void
    {
        $this->client->request('GET', '/api/titles/99999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteTitleReturns405(): void
    {
        $title = $this->createTitle();

        $this->client->request('DELETE', '/api/titles/' . $title->getId());

        $this->assertResponseStatusCodeSame(405);
    }

    // --- Write operation tests ---

    private function post(array $data): void
    {
        $this->client->request('POST', '/api/titles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => $data,
        ]);
    }

    public function testPostEndpointCreatesTitleAndReturns201(): void
    {
        $this->client->request('POST', '/api/titles', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'title' => 'Inception',
                'director' => 'Christopher Nolan',
                'releaseYear' => 2010,
                'durationMinutes' => 148,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testPutEndpointUpdatesTitleAndReturns200(): void
    {
        $title = $this->createTitle();

        $this->client->request('PUT', '/api/titles/' . $title->getId(), [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'title' => 'Updated Title',
                'director' => 'Updated Director',
                'releaseYear' => 2020,
                'durationMinutes' => 120,
            ],
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testPatchEndpointUpdatesTitleAndReturns200(): void
    {
        $title = $this->createTitle();

        $this->client->request('PATCH', '/api/titles/' . $title->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['title' => 'Patched Title'],
        ]);

        $this->assertResponseIsSuccessful();
    }

    // --- Validation tests ---

    public function testTitleIsRequiredReturns422(): void
    {
        $this->post(['director' => 'Christopher Nolan', 'releaseYear' => 2010]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testDirectorIsRequiredReturns422(): void
    {
        $this->post(['title' => 'Inception', 'releaseYear' => 2010]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testReleaseYearIsRequiredReturns422(): void
    {
        $this->post(['title' => 'Inception', 'director' => 'Christopher Nolan']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testReleaseYearBelowMinReturns422(): void
    {
        $this->post(['title' => 'Inception', 'director' => 'Christopher Nolan', 'releaseYear' => 1887]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testReleaseYearAboveMaxReturns422(): void
    {
        $this->post(['title' => 'Inception', 'director' => 'Christopher Nolan', 'releaseYear' => 2101]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testDurationMinutesBelowMinReturns422(): void
    {
        $this->post(['title' => 'Inception', 'director' => 'Christopher Nolan', 'releaseYear' => 2010, 'durationMinutes' => 0]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testDurationMinutesAboveMaxReturns422(): void
    {
        $this->post(['title' => 'Inception', 'director' => 'Christopher Nolan', 'releaseYear' => 2010, 'durationMinutes' => 601]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testPutNonExistentTitleReturns404(): void
    {
        $this->client->request('PUT', '/api/titles/99999', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'title' => 'Ghost Title',
                'director' => 'Ghost Director',
                'releaseYear' => 2000,
                'durationMinutes' => 90,
            ],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPatchNonExistentTitleReturns404(): void
    {
        $this->client->request('PATCH', '/api/titles/99999', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['title' => 'Ghost Patch'],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPostWithoutDurationMinutesReturns201(): void
    {
        $this->post(['title' => 'Inception', 'director' => 'Christopher Nolan', 'releaseYear' => 2010]);

        $this->assertResponseStatusCodeSame(201);
    }
}
