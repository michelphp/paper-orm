<?php

namespace Michel\PaperORM\Types;

use InvalidArgumentException;

final class AnyType extends Type
{

    /** @var int Max allowed serialized length */
    private int $maxLength = 150;

    /**
     * Converts a PHP value into a database-storable string.
     *
     * @param mixed $value
     * @return string
     * @throws \InvalidArgumentException
     */
    public function convertToDatabase($value) : string
    {
        $type = strtolower(gettype($value));

        switch ($type) {
            case 'null':
                $encoded = 'null:';
                break;

            case 'boolean':
                $encoded = 'boolean:' . ($value ? '1' : '0');
                break;

            case 'integer':
            case 'double':
            case 'float':
                $encoded = $type . ':' . (string) $value;
                break;

            case 'string':
                $encoded = 'string:' . trim($value);
                break;

            case 'array':
                $json = json_encode($value);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \InvalidArgumentException(
                        'JSON encoding error while serializing array: ' . json_last_error_msg()
                    );
                }
                $encoded = 'array:' . $json;
                break;

            case 'object':
                $serialized = @serialize($value);
                if (empty($serialized)) {
                    throw new \InvalidArgumentException('Failed to serialize object.');
                }
                $encoded = 'object:' . $serialized;
                break;

            default:
                throw new \InvalidArgumentException(sprintf(
                    'Unsupported type "%s" for database conversion.',
                    $type
                ));
        }

        if (strlen($encoded) > $this->maxLength) {
            throw new \InvalidArgumentException(sprintf(
                'AnyColumn value too long (%d bytes, max %d). Use a dedicated column type for large data.',
                strlen($encoded),
                $this->maxLength
            ));
        }

        return $encoded;
    }

    /**
     * Converts a database value back to its original PHP representation.
     *
     * @param string|null $value
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function convertToPHP($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Expected format: "<type>:<value>"
        $parts = explode(':', $value, 2);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException(
                sprintf('Invalid data format for AnyColumn: "%s"', $value)
            );
        }

        list($type, $raw) = $parts;
        $type = strtolower(trim($type));

        switch ($type) {
            case 'null':
                return null;

            case 'boolean':
                $val = strtolower(trim($raw));
                if ($val === '1') {
                    return true;
                }
                if ($val === '0') {
                    return false;
                }
                throw new \InvalidArgumentException("Invalid boolean value: '{$raw}'");

            case 'integer':
            case 'int':
                if (!is_numeric($raw)) {
                    throw new \InvalidArgumentException("Invalid integer value: '{$raw}'");
                }
                return (int) $raw;

            case 'double':
            case 'float':
                if (!is_numeric($raw)) {
                    throw new \InvalidArgumentException("Invalid numeric value: '{$raw}'");
                }
                return (float) $raw;

            case 'string':
                return (string) $raw;

            case 'array':
                $decoded = json_decode($raw, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                    throw new \InvalidArgumentException(
                        'JSON decoding error while converting to array: ' . json_last_error_msg()
                    );
                }
                return $decoded;

            case 'object':
                $obj = @unserialize($raw);
                if ($obj === false && $raw !== 'b:0;') {
                    throw new \InvalidArgumentException(
                        'Object deserialization failed: invalid or corrupted data.'
                    );
                }
                return $obj;

            default:
                throw new \InvalidArgumentException(sprintf(
                    'Unsupported type "%s" during PHP conversion.',
                    $type
                ));
        }
    }
}
