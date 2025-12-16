<?php

namespace Michel\PaperORM;

use LogicException;
use Michel\EventDispatcher\ListenerProvider;
use Michel\PaperORM\Cache\EntityMemcachedCache;
use Michel\PaperORM\Driver\DriverManager;
use Michel\PaperORM\Event\Create\PostCreateEvent;
use Michel\PaperORM\Event\Create\PreCreateEvent;
use Michel\PaperORM\Event\Update\PreUpdateEvent;
use Michel\PaperORM\EventListener\PostCreateEventListener;
use Michel\PaperORM\EventListener\PreCreateEventListener;
use Michel\PaperORM\EventListener\PreUpdateEventListener;
use Michel\PaperORM\Parser\DSNParser;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;

final class PaperConfiguration
{
    private PaperConnection $connection;
    private UnitOfWork $unitOfWork;
    private EntityMemcachedCache $cache;
    private ListenerProviderInterface $listeners;
    private function __construct(
        PaperConnection           $connection,
        UnitOfWork                $unitOfWork,
        EntityMemcachedCache      $cache,
        ListenerProviderInterface $listeners
    )
    {
        $this->connection = $connection;
        $this->unitOfWork = $unitOfWork;
        $this->cache = $cache;
        $this->listeners = $listeners;
        $this->registerDefaultListeners();
    }

    public static function fromDsn(string $dsn, bool $debug = false): self
    {
        if ($dsn === '') {
            throw new LogicException('PaperConfiguration::fromDsn(): DSN cannot be empty.');
        }

        $params = DSNParser::parse($dsn);
        return self::fromArray($params, $debug);
    }

    public static function fromArray(array $params, bool $debug = false): self
    {
        $params['extra']['debug'] = $debug;
        $connection = DriverManager::createConnection($params['driver'], $params);
        return new self($connection, new UnitOfWork(), new EntityMemcachedCache(), new ListenerProvider());
    }

    public function withLogger(LoggerInterface $logger): self
    {
        $this->connection->withLogger($logger);
        return $this;
    }

    public function withListener(string $event, callable $listener): self
    {
        $provider = $this->listeners;
        $provider->addListener($event, $listener);

        return $this;
    }

    public function getConnection(): PaperConnection
    {
        return $this->connection;
    }

    public function getUnitOfWork(): UnitOfWork
    {
        return $this->unitOfWork;
    }

    public function getCache(): EntityMemcachedCache
    {
        return $this->cache;
    }

    public function getListeners(): ListenerProviderInterface
    {
        return $this->listeners;
    }

    private function registerDefaultListeners(): void
    {
        $this->listeners
            ->addListener(PreCreateEvent::class, new PreCreateEventListener())
            ->addListener(PostCreateEvent::class, new PostCreateEventListener())
            ->addListener(PreUpdateEvent::class, new PreUpdateEventListener())
        ;
    }
}
