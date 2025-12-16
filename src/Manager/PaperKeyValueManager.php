<?php

namespace Michel\PaperORM\Manager;

use Michel\PaperORM\EntityManagerInterface;
use Michel\PaperORM\Internal\Entity\PaperKeyValue;
use Michel\PaperORM\Repository\Repository;

final class PaperKeyValueManager
{
    private EntityManagerInterface $em;
    private Repository $repository;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->repository = $em->getRepository(PaperKeyValue::class);
    }

    public function get(string $key)
    {
        $kv = $this->repository->findOneBy(['key' => strtolower($key)])->toArray();
        if (empty($kv)) {
            return null;
        }
        return $kv['value'];
    }

    public function set(string $key, $value): void
    {
        $kv = $this->repository->findOneBy(['key' => strtolower($key)])->toObject();
        if (!$kv instanceof PaperKeyValue) {
            $kv = new PaperKeyValue();
            $kv->setKey(strtolower($key));
        }
        $kv->setValue($value);

        $this->em->persist($kv);
        $this->em->flush($kv);
    }

    public function remove(string $key): void
    {
        if ($kv = $this->repository->findOneBy(['key' => $key])->toObject()) {
            $this->em->remove($kv);
            $this->em->flush($kv);
        }
    }

}
