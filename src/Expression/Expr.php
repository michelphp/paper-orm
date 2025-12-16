<?php

namespace Michel\PaperORM\Expression;

use LogicException;

class Expr
{
    private string $key;
    private string $operator;
    private $value;
    private ?string $alias;
    private bool $prepared = false;

    public function __construct(string $key, string $operator, $value = null)
    {
        if ( ($operator === 'IN' || $operator === 'NOT IN') && !is_array($value)) {
            throw new LogicException('IN and NOT IN operators require an array '. gettype($value) . ' given');
        }

        $this->key = $key;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function toPrepared(string $alias = null): string
    {
        $this->prepared = true;
        $this->alias = $alias;

        $str = $this->__toString();

        $this->prepared = false;
        $this->alias = null;

        return $str;
    }

    public function __toString(): string
    {
        $key = $this->key;
        if ($this->alias !== null) {
            $key = sprintf('%s.%s', $this->alias, $this->key);
        }

        $value = $this->getValue();
        if ($this->prepared) {
            $value = [];
            foreach ($this->getBoundValue() as $k => $v) {
                $value[] = $k;
            }
            $value = implode(', ', $value);
        }


        switch ($this->operator) {
            case '=':
                $str = "$key = $value";
                break;
            case '!=':
                $str = "$key <> $value";
                break;
            case '>':
                $str = "$key > $value";
                break;
            case '>=':
                $str = "$key >= $value";
                break;
            case '<':
                $str = "$key < $value";
                break;
            case '<=':
                $str = "$key <= $value";
                break;
            case 'NULL':
                $str = "$key IS NULL";
                break;
            case '!NULL':
                $str = "$key IS NOT NULL";
                break;
            case 'IN':
                $str = "$key IN (" . $value . ")";
                break;
            case '!IN':
                $str = "$key NOT IN (" . $value . ")";
                break;
            default:
                throw new LogicException('Unknown operator ' . $this->operator);
        }

        return $str;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getAliasKey(): string
    {
        return ':' . $this->getKey();
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getBoundValue()
    {
        if ($this->getValue() === null) {
            return [];
        }
        
        if ($this->operator === 'IN' || $this->operator === '!IN') {
            $value = [];
            foreach ($this->getValue() as $k => $v) {
                $key = $this->getAliasKey() . '_' . $k;
                $value[$key] = $v;
            }
            return $value;
        }
        return [$this->getAliasKey() => $this->getValue()];
    }

    public static function equal(string $key, $value): self
    {
        return new self($key, '=', $value);
    }

    public static function notEqual(string $key, $value): self
    {
        return new self($key, '!=', $value);
    }

    public static function greaterThan(string $key, $value): self
    {
        return new self($key, '>', $value);
    }

    public static function greaterThanEqual(string $key, $value): self
    {
        return new self($key, '>=', $value);
    }

    public static function lowerThan(string $key, $value): self
    {
        return new self($key, '<', $value);
    }

    public static function lowerThanEqual(string $key, $value): self
    {
        return new self($key, '<=', $value);
    }

    public static function isNull(string $key): self
    {
        return new self($key, 'NULL');
    }

    public static function isNotNull(string $key): self
    {
        return new self($key, '!NULL');
    }

    public static function in(string $key, array $values): self
    {
        return new self($key, 'IN', $values);
    }

    public static function notIn(string $key, array $values): self
    {
        return new self($key, '!IN', $values);
    }
}
