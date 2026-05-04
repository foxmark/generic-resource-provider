<?php

namespace App\Event\Trait;

trait EntityEventTrait {
        
    public static function getPostCreateEventName(): string
    {
        return self::getEntityClassName() . '.post.create';
    }

    public static function getPostUpdateEventName(): string
    {
        return self::getEntityClassName() . '.post.update';
    }

    public static function getPreCreateEventName(): string
    {
        return self::getEntityClassName() . '.pre.create';
    }

    public static function getPreUpdateEventName(): string
    {
        return self::getEntityClassName() . '.pre.update';
    }
}