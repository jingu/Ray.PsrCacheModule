<?php

declare(strict_types=1);

namespace Ray\PsrCacheModule;

use Ray\Di\ProviderInterface;
use Ray\PsrCacheModule\Annotation\RedisCluster as RedisClusterAnnotation;
use Ray\PsrCacheModule\Annotation\RedisConfig;
use Ray\PsrCacheModule\Exception\RedisConnectionException;
use Redis;
use RedisCluster;

use function explode;
use function sprintf;
use function strpos;

/** @implements ProviderInterface<Redis|RedisCluster> */
class RedisProvider implements ProviderInterface
{
    /** @var list<string> */
    private $servers;

    /** @var bool */
    private $cluster;

    /**
     * @param list<string> $servers
     *
     * @RedisConfig("server")
     * @RedisClusterAnnotation("cluster")
     */
    #[RedisConfig('servers')]
    #[RedisClusterAnnotation('cluster')]
    public function __construct(array $servers, bool $cluster)
    {
        $this->servers = $servers;
        $this->cluster = $cluster;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        if ($this->cluster) {
            return new RedisCluster(null, $this->servers);
        }

        $redis = new Redis();
        [$host, $port] = explode(':', $this->servers[0], 2);
        if (strpos($port, ':') !== false) {
            [$port, $dbIndex] = explode(':', $port, 2);
        }

        $connected = $redis->connect($host, (int) $port);
        if (isset($dbIndex)) {
            $redis->select((int) $dbIndex);
        }

        if (! $connected) {
            throw new RedisConnectionException(sprintf('%s:%s', $host, $port)); // @codeCoverageIgnore
        }

        return $redis;
    }
}
