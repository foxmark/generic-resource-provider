<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\State\Processor\GenericDtoProcessor;
use App\State\Provider\GenericDtoProvider;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Title',
    operations: [
        new GetCollection(provider: GenericDtoProvider::class),
        new Get(provider: GenericDtoProvider::class),
        new Post(processor: GenericDtoProcessor::class),
        new Put(provider: GenericDtoProvider::class, processor: GenericDtoProcessor::class),
        new Patch(provider: GenericDtoProvider::class, processor: GenericDtoProcessor::class),
    ],
    paginationEnabled: true,
    paginationItemsPerPage: 10,
    paginationClientItemsPerPage: true,
    paginationMaximumItemsPerPage: 30,
)]
class TitleResource
{
    public ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    public ?string $title = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $director = null;

    #[Assert\NotBlank]
    #[Assert\Range(min: 1888, max: 2100)]
    public ?int $releaseYear = null;

    #[Assert\Range(min: 1, max: 600)]
    public ?int $durationMinutes = null;
}
