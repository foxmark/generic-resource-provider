<?php
// src/ApiResource/BookResource.php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Book',
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