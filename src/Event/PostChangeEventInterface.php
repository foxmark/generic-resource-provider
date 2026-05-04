<?php

namespace App\Event;

interface PostChangeEventInterface extends ChangeEventInterface{
    public static function getPostCreateEventName(): string;
    public static function getPostUpdateEventName(): string;
}