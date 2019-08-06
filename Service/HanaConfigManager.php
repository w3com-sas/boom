<?php

namespace W3com\BoomBundle\Service;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use W3com\BoomBundle\Service\BoomManager;

/**
 * Class HanaConfigManager.
 */
class HanaConfigManager
{
    const STORAGE_KEY = 'hana.config';

    /**
     * @var BoomManager
     */
    private $boom;

    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * HanaConfigManager constructor.
     *
     * @param BoomManager      $boom
     * @param AdapterInterface $cache
     */
    public function __construct(BoomManager $boom, AdapterInterface $cache)
    {
        $this->boom = $boom;
        $this->cache = $cache;
    }

    /**
     * @return array
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function getConfig(): array
    {

        $cachedConfig = $this->cache->getItem($this::STORAGE_KEY);
        if (!$cachedConfig->isHit()) {
            $configAr = $this->boom->getRepository('Config')->findAll();
            $config = [];
            foreach ($configAr as $item) {
                if (!array_key_exists($item->getConfigType(), $config)) {
                    $config[$item->getConfigType()] = [];
                }
                $config[$item->getConfigType()][$item->getKey()] = $item->getValue();
            }
            $cachedConfig->set($config);
            $this->cache->save($cachedConfig);
        } else {
            $config = $cachedConfig->get();
        }

        return $config;
    }
}
