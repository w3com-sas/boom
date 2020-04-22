<?php

namespace W3com\BoomBundle\Form\Type;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use W3com\BoomBundle\HanaEntity\AbstractEntity;
use W3com\BoomBundle\Service\BoomManager;

class HanaEntityChoiceType extends AbstractType
{
    private $boom;

    private $cache;

    public function __construct(BoomManager $boom, AdapterInterface $cache)
    {
        $this->boom = $boom;
        $this->cache = $cache;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choiceList = $this->getChoices($options);
        $builder->setAttribute('choice_list', $choiceList);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['choice_value', 'choice_label', 'hana_entity'])
            ->setAllowedTypes('hana_entity', [AbstractEntity::class]);

        $resolver->setDefaults([
            'cache_storage_key' => null,
            'cache_expire' => '1 month'
        ]);
    }

    private function getChoices(array $options): array
    {
        if (!$options['cache_storage_key'] || empty($this->getCacheData($options))) {
            $data = $this->getBoomData($options);
        }

        if (isset($data) && $options['cache_storage_key']) {
            $cacheItem = $this->cache->getItem($options['cache_storage_key']);
            $cacheItem
                ->set($data)
                ->expiresAfter(\DateInterval::createFromDateString($options['cache_expire']));
            $this->cache->save($cacheItem);
        }

        if (!isset($data)) {
            $data = $this->getCacheData($options);
        }

        $choices = [];
        /** @var AbstractEntity $entity */
        foreach ($data as $entity) {
            $choices[$entity->get($options['choice_label'])] = $entity->get($options['choice_value']);
        }
        return $choices;
    }

    private function getBoomData(array $options)
    {
        $repo = $this->boom->getRepository($options['hana_entity']);
        $params = $repo->createParams()->addSelect([$options['choice_value'], $options['choice_label']]);
        return $repo->findAll($params);
    }

    private function getCacheData(array $options)
    {
        $cacheItem = $this->cache->getItem($options['cache_storage_key']);
        return $cacheItem->get();
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

}