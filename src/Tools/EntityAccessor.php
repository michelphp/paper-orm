<?php

namespace Michel\PaperORM\Tools;

class EntityAccessor
{
    public static function getValue(object $entity, string $property)
    {
        $methods   = ["get" . ucfirst($property), "is" . ucfirst($property)];
        foreach ($methods as $method) {
            if (method_exists($entity, $method)) {
                return $entity->$method();
            }
        }

        if (array_key_exists($property, get_object_vars($entity))) {
            return $entity->$property;
        }

        throw new \LogicException(sprintf(
            'Cannot get value: expected getter "%s()" or a public property "%s" in entity "%s".',
            $method,
            $property,
            get_class($entity)
        ));

    }

    public static function setValue(object $entity, string $property, $value)
    {
        $method   = "set" . ucfirst($property);
        if (method_exists($entity, $method)) {
            $entity->$method($value);
        } elseif (array_key_exists($property, get_object_vars($entity))) {
            $entity->$property = $value;
        } else {
            throw new \LogicException(sprintf(
                'Cannot set value: expected setter "%s()" or a public property "%s" in entity "%s".',
                $method,
                $property,
                get_class($entity)
            ));
        }
    }

}
