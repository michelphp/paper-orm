<?php

namespace Test\Michel\PaperORM\Entity;

use Michel\PaperORM\Entity\EntityInterface;
use Michel\PaperORM\Mapping\Column\AutoIncrementColumn;
use Michel\PaperORM\Mapping\Column\JoinColumn;
use Michel\PaperORM\Mapping\Column\PrimaryKeyColumn;
use Michel\PaperORM\Mapping\Entity;

#[Entity(table: 'invoice', repository: null)]
class InvoiceTest implements EntityInterface
{
    #[PrimaryKeyColumn]
    private ?int $id = null;
    #[AutoIncrementColumn(pad: 8, prefix: 'INV-{YYYY}-', key: 'invoice.number')]
    private ?string $number = null;

    #[AutoIncrementColumn(pad: 8, key: 'invoice.code')]
    private ?string $code = null;


    static public function getTableName(): string
    {
        return 'invoice';
    }

    static public function getRepositoryName(): ?string
    {
        return null;
    }

    static public function columnsMapping(): array
    {
        return [
            (new PrimaryKeyColumn())->bindProperty('id'),
            (new AutoIncrementColumn(null, 'invoice.number', 6, 'INV-{YYYY}-'))->bindProperty('number'),
            (new AutoIncrementColumn(null, 'invoice.code', 8, null))->bindProperty('code')
        ];
    }

    public function getPrimaryKeyValue(): ?int
    {
        return $this->getId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): InvoiceTest
    {
        $this->number = $number;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): InvoiceTest
    {
        $this->code = $code;
        return $this;
    }


}
