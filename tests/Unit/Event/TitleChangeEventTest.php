<?php

declare(strict_types=1);

namespace App\Tests\Unit\Event;

use App\Entity\Title;
use App\Event\TitleChangeEvent;
use PHPUnit\Framework\TestCase;

class TitleChangeEventTest extends TestCase
{
    public function testGetPostCreateEventNameReturnsTitlePostCreate(): void
    {
        $title = new Title();
        $event = new TitleChangeEvent($title);

        $this->assertSame('Title.post.create', TitleChangeEvent::getPostCreateEventName());
    }
}
