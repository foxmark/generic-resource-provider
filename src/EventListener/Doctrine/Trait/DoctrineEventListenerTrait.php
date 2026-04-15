<?php

namespace App\EventListener\Doctrine\Trait;

trait DoctrineEventListenerTrait
{
    public static function getEventClassName($entity): string
    {
        $parts = explode('\\', $entity::class);
        $entityName = end($parts);

        return 'App\\Event\\'. $entityName .'ChangeEvent';
    }
}