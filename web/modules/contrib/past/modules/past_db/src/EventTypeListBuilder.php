<?php

namespace Drupal\past_db;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder for the Past event type bundle.
 *
 * @see \Drupal\past_db\Entity\PastEventType
 */
class EventTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['label'] .= ' <small>(' . $this->t('Machine name: @name', ['@name' => $entity->id()]) . ')</small>';
    return $row + parent::buildRow($entity);
  }

}
