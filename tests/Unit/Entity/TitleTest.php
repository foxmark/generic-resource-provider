<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Title;
use App\EventListener\Doctrine\Interface\NotifiableInsertInterface;
use PHPUnit\Framework\TestCase;

class TitleTest extends TestCase
{
    public function testTitleImplementsNotifiableInsertInterface(): void
    {
        $title = new Title();

        $this->assertInstanceOf(NotifiableInsertInterface::class, $title);
    }
}
