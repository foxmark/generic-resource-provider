<?php
// src/ApiResource/BookResource.php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\State\Processor\GenericDtoProcessor;
use App\State\Provider\GenericDtoProvider;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Book',
    operations: [
        new GetCollection(provider: GenericDtoProvider::class),
        new Get(provider: GenericDtoProvider::class),
        new Post(processor: GenericDtoProcessor::class),
        new Put(provider: GenericDtoProvider::class, processor: GenericDtoProcessor::class),
        new Patch(provider: GenericDtoProvider::class, processor: GenericDtoProcessor::class),
        new Delete(provider: GenericDtoProvider::class, processor: GenericDtoProcessor::class),
    ],
    paginationEnabled: true,
    paginationItemsPerPage: 10,
    paginationClientItemsPerPage: true,
    paginationMaximumItemsPerPage: 30,
)]
class BookResource
{
    public ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $title = null;

    #[Assert\NotBlank]
    #[Assert\Isbn]
    public ?string $isbn = null;

    #[Assert\NotBlank]
    public ?string $authorName = null;

    #[Assert\Range(min: 1000, max: 2100)]
    public ?int $publicationYear = null;

    public bool $available = true;
}