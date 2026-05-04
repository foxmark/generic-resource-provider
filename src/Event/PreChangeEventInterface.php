<?php

namespace App\Event;

interface PreChangeEventInterface extends ChangeEventInterface{
    public static function getPreCreateEventName(): string;
    public static function getPreUpdateEventName(): string;
}