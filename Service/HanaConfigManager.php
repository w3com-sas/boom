<?php

namespace W3com\BoomBundle\Service;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

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
     * @var FilesystemAdapter
     */
    private $cache;

    /**
     * HanaConfigManager constructor.
     *
     * @param BoomManager      $boom
     * @param FilesystemAdapter $cache
     */
    public function __construct(BoomManager $boom, FilesystemAdapter $cache)
    {
        $this->boom = $boom;
        $this->cache = $cache;
    }

    /**
     * @return array
     *
     * @throws InvalidArgumentException
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

    /**
     * @param $configType
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getConfigTypeList($configType)
    {
        $config = $this->getConfig();
        if(!array_key_exists($configType,$config)){
            throw new \Exception('Liste '.$configType.' inconnue');
        }
        return $config[$configType];
    }

    /**
     * @param $configType
     * @param $value
     * @return int|string
     * @throws InvalidArgumentException
     */
    public function getKey($configType, $value)
    {
        $list = $this->getConfigTypeList($configType);
        if(!is_array($list) || count($list) == 0){
            throw new \Exception('Liste '.$configType.' inconnue ou vide');
        }
        foreach($list as $configKey=>$configValue){
            if($configValue == $value){
                return $configKey;
            }
        }
        throw new \Exception('La valeur demandée n\'a pas été trouvé dans la liste '.$configType);
    }

    /**
     * @param $configType
     * @param $key
     * @return mixed
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function getValue($configType, $key)
    {
        $list = $this->getConfigTypeList($configType);
        if(!is_array($list) || count($list) == 0){
            throw new \Exception('Liste '.$configType.' inconnue ou vide');
        }
        foreach($list as $configKey=>$configValue){
            if($configKey == $key){
                return $configValue;
            }
        }
        throw new \Exception('La clé demandée n\'a pas été trouvé dans la liste '.$configType);
    }
}
