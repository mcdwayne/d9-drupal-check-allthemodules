<?php

namespace Drupal\drupaneo_standalone\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of product entities.
 */
class ProductListBuilder extends EntityListBuilder {

    /**
     * {@inheritdoc}
     */
    public function buildHeader() {
        return [
            'identifier' => [
                'data' => $this->t('Identifier'),
                'field' => 'identifier',
                'specifier' => 'identifier',
            ],
            'family' => [
                'data' => $this->t('Family'),
                'field' => 'family',
                'specifier' => 'family',
            ],
            'created' => [
                'data' => $this->t('Created'),
                'field' => 'created',
                'specifier' => 'created',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load() {
        $entity_query = \Drupal::service('entity.query')->get('product');

        $header = $this->buildHeader();
        $entity_query->pager($this->limit);
        $entity_query->tableSort($header);

        $uids = $entity_query->execute();
        return $this->storage->loadMultiple($uids);
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity) {
        return array(
            'identifier' => $entity->get('identifier')->getString(),
            'family' => $entity->get('family')->getString(),
            'created' => format_date($entity->get('created')->getString(), 'short'),
        );
    }
}
