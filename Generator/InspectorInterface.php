<?php

namespace W3com\BoomBundle\Generator;


interface InspectorInterface
{
    public function getEntity($name);

    public function getEntities();

    public function initEntities();
}