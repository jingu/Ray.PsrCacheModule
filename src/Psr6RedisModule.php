<?php

declare(strict_types=1);

namespace Ray\PsrCacheModule;

use Psr\Cache\CacheItemPoolInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Ray\PsrCacheModule\Annotation\CacheNamespace;
use Ray\PsrCacheModule\Annotation\Local;
use Ray\PsrCacheModule\Annotation\RedisConfig;
use Ray\PsrCacheModule\Annotation\RedisInstance;
use Ray\PsrCacheModule\Annotation\Shared;
use Symfony\Component\Cache\Adapter\RedisAdapter;

use function array_map;
use function explode;

final class Psr6RedisModule extends AbstractModule
{
    /** @var list<list<string>> */
    private $servers;

    public function __construct(string $servers, ?AbstractModule $module = null)
    {
        $this->servers = array_map(static function ($serverString) {
            return explode(':', $serverString);
        }, explode(',', $servers));
        parent::__construct($module);
    }

    protected function configure(): void
    {
        $this->bind(CacheItemPoolInterface::class)->annotatedWith(Local::class)->toProvider(LocalCacheProvider::class)->in(Scope::SINGLETON);
        $this->bind(CacheItemPoolInterface::class)->annotatedWith(Shared::class)->toConstructor(RedisAdapter::class, [
            'redisClient' => RedisInstance::class,
            'namespace' => CacheNamespace::class,
        ])->in(Scope::SINGLETON);
        $this->bind()->annotatedWith(RedisConfig::class)->toInstance($this->servers);
        $this->bind('')->annotatedWith('Ray\PsrCacheModule\Annotation\RedisInstance')->toProvider(RedisProvider::class);
    }
}
