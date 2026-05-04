<?php

namespace App\Event;

use App\Entity\Book;
use App\Event\PostChangeEventInterface;
use App\Event\PreChangeEventInterface;
use App\Event\Trait\EntityEventTrait;
use Symfony\Contracts\EventDispatcher\Event;

class BookChangeEvent extends Event implements PostChangeEventInterface, PreChangeEventInterface
{
    use EntityEventTrait;

    private Book $book;

    public function __construct(Book $book)
    {
        $this->book =  $book;    
    }

    public function getBook(): Book
    {
        return $this->book;
    }

    public static function getEntityClassName(): string
    {
        $parts = explode('\\', Book::class);
        return end($parts);
    }
}
