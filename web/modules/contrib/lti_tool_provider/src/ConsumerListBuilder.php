<?php

namespace Drupal\lti_tool_provider;

use Drupal;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\lti_tool_provider\Entity\Consumer;

class ConsumerListBuilder extends EntityListBuilder
{
    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header = [
            'id' => [
                'data' => $this->t('ID'),
                'field' => 'id',
                'specifier' => 'id',
            ],
            'consumer' => [
                'data' => $this->t('Label'),
                'field' => 'consumer',
                'specifier' => 'consumer',
                'class' => [RESPONSIVE_PRIORITY_LOW],
            ],
            'consumer_key' => [
                'data' => $this->t('Label'),
                'field' => 'consumer_key',
                'specifier' => 'consumer_key',
                'class' => [RESPONSIVE_PRIORITY_LOW],
            ],
            'consumer_secret' => [
                'data' => $this->t('Label'),
                'field' => 'consumer_secret',
                'specifier' => 'consumer_secret',
                'class' => [RESPONSIVE_PRIORITY_LOW],
            ],
            'created' => [
                'data' => $this->t('Created'),
                'field' => 'created',
                'specifier' => 'created',
                'sort' => 'desc',
                'class' => [RESPONSIVE_PRIORITY_LOW],
            ],
        ];

        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        $row = [];

        if ($entity instanceof Consumer) {
            $row = [
                'id' => $entity->id(),
                'consumer' => $link = Drupal\Core\Link::fromTextAndUrl(
                    $entity->label(),
                    Url::fromRoute(
                        'entity.lti_tool_provider_consumer.canonical',
                        ['lti_tool_provider_consumer' => $entity->id()]
                    )
                ),
                'consumer_key' => $entity->get('consumer_key')->value,
                'consumer_secret' => $entity->get('consumer_secret')->value,
                'created' => Drupal::service('date.formatter')->format($entity->get('created')->value, 'short'),
            ];
        }

        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $build = parent::render();

        $build['table']['#empty'] = $this->t('No consumers found.');

        return $build;
    }
}
