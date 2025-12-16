<?php

namespace Michel\PaperORM\Schema\Traits;

trait IdentifierQuotingTrait
{

    public function quotes(array $collection): array
    {
        return array_map([$this, 'quote'], $collection);
    }
    public function quote(string $identifier): string
    {
        $identifiers = $this->getIdentifierQuoteSymbols();
        if (count($identifiers) != 2) {
            throw new \LogicException('The method getIdentifierQuoteSymbols() must be an array with 2 elements, ex : ["`", "`"] : ' . __CLASS__);
        }
        [$open, $close] = $identifiers;
        if (strlen($identifier) > 2 && $identifier[0] === $open && $identifier[strlen($identifier) - 1] === $close) {
            return $identifier;
        }
        return $open . $identifier . $close;
    }

    abstract public function getIdentifierQuoteSymbols(): array;
}
