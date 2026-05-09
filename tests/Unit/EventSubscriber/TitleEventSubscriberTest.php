<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\TitleEventSubscriber;
use PHPUnit\Framework\TestCase;

class TitleEventSubscriberTest extends TestCase
{
    public function testSubscribesToTitlePostCreateOnly(): void
    {
        $events = TitleEventSubscriber::getSubscribedEvents();

        $this->assertCount(1, $events);
        $this->assertArrayHasKey('Title.post.create', $events);
        $this->assertSame('onTitleCreated', $events['Title.post.create']);
    }
}
