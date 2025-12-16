<?php

namespace Michel\PaperORM\Types;

final class DateType extends Type
{

    public function convertToDatabase($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($this->getSchema()->getDateFormatString());
        }

        throw new \LogicException('Could not convert PHP value "' . $value . '" to ' . self::class);
    }

    public function convertToPHP($value): ?\DateTimeInterface
    {
        if ($value === null || $value instanceof \DateTimeInterface) {
            return $value;
        }

        $date = \DateTime::createFromFormat($this->getSchema()->getDateFormatString(),$value);
        if (!$date instanceof \DateTimeInterface) {
            throw new \LogicException('Could not convert database value "' . $value . '" to ' . self::class);
        }

        return $date;
    }

}
