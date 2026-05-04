<?php

namespace App\Event;

interface ChangeEventInterface
{
    public static function getEntityClassName(): string;
}