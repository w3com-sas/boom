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

    /**
     * @return int
     */
    public function countErrors()
    {
        $errors = 0;
        if (is_array($this->data) && count($this->data) > 0) {
            foreach ($this->data as $element)
            {
                if (array_key_exists('code', $element) && in_array(substr($element['code'], 0, 1), ['4', '5'])) $errors++;
            }
        }
        return $errors;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return $this->countErrors() > 0;
    }
}
