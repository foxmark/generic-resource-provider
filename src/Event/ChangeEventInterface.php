<?php

namespace App\Event;


interface ChangeEventInterface{
    public static function getCreatedEventName(): string;
    public static function getUpdatedEventName(): string;
}