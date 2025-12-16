<?php

namespace Michel\PaperORM\Proxy;

interface ProxyInterface
{
    public function __setInitialized(array $propertiesInitialized);

    public function __isInitialized(): bool;

    public function __wasModified(): bool;

    public function __getPropertiesModified() : array;

    public function __destroy(): void;

    public function __reset(): void;

    public function __getParentClass(): string;
}
