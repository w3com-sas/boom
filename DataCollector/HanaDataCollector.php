<?php

namespace W3com\BoomBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use W3com\BoomBundle\Service\BoomManager;

class HanaDataCollector extends DataCollector
{
    /**
     * @var BoomManager
     */
    protected $boomManager;

    /**
     * HanaDataCollector constructor.
     *
     * @param BoomManager $manager
     */
    public function __construct(BoomManager $manager)
    {
        $this->boomManager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = $this->boomManager->getCollectedData();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'boom.hana_collector';
    }

    public function getData()
    {
        return $this->data;
    }

    public function reset()
    {
        $this->data = null;
    }
}
