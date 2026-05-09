<?php

namespace App\Event;

use App\Entity\Title;
use App\Event\PostChangeEventInterface;
use App\Event\Trait\EntityEventTrait;
use Symfony\Contracts\EventDispatcher\Event;

class TitleChangeEvent extends Event implements PostChangeEventInterface
{
    use EntityEventTrait;

    private Title $title;

    public function __construct(Title $title)
    {
        $this->title = $title;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public static function getEntityClassName(): string
    {
        $parts = explode('\\', Title::class);
        return end($parts);
    }
}
