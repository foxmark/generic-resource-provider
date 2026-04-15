<?php

namespace App\Event;

use App\Entity\Book;
use App\Event\ChangeEventInterface;
use Symfony\Contracts\EventDispatcher\Event;

class BookChangeEvent extends Event implements ChangeEventInterface
{
    public const CREATED = 'book.created';
    public const UPDATED = 'book.updated';

    private Book $book;

    public function __construct(Book $book)
    {
        $this->book =  $book;    
    }

    public static function getCreatedEventName(): string
    {
        return self::CREATED;
    }

    public static function getUpdatedEventName(): string
    {
        return self::UPDATED;
    }

    public function getBook(): Book
    {
        return $this->book;
    }
}
