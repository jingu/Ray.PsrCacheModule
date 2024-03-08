<?php

declare(strict_types=1);

namespace Ray\PsrCacheModule;

use Psr\Cache\CacheItemPoolInterface;
use Ray\Di\AbstractModule;
use Ray\Di\ProviderInterface;
use Ray\Di\Scope;
use Ray\PsrCacheModule\Annotation\CacheNamespace;
use Ray\PsrCacheModule\Annotation\Local;
use Ray\PsrCacheModule\Annotation\RedisCluster;
use Ray\PsrCacheModule\Annotation\RedisConfig;
use Ray\PsrCacheModule\Annotation\Shared;
use Redis;
use RuntimeException;

use function class_exists;
use function explode;

final class Psr6RedisModule extends AbstractModule
{
    /** @var list<string> */
    private $servers;
    private bool $isCluster;

    public function __construct(
        string $servers,
        bool $isCluster,
        ?AbstractModule $module = null
    ) {
        $this->servers = explode(',', $servers);
        $this->isCluster = $isCluster;

        parent::__construct($module);
    }

    protected function configure(): void
    {
        if (! class_exists(Redis::class)) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Redis not installed.');
            // @codeCoverageIgnoreEnd
        }

        $this->bind(CacheItemPoolInterface::class)->annotatedWith(Local::class)->toConstructor(ApcuAdapter::class, ['namespace' => CacheNamespace::class])->in(Scope::SINGLETON);
        $this->bind(CacheItemPoolInterface::class)->annotatedWith(Shared::class)->toConstructor(RedisAdapter::class, [
            'redisProvider' => 'redis',
            'namespace' => CacheNamespace::class,
        ]);
        $this->bind()->annotatedWith(RedisConfig::class)->toInstance($this->servers);
        $this->bind()->annotatedWith(RedisCluster::class)->toInstance($this->isCluster);
        $this->bind(ProviderInterface::class)->annotatedWith('redis')->to(RedisProvider::class);
    }
}
