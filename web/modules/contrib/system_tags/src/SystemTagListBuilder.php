<?php

namespace Drupal\system_tags;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class SystemTagListBuilder.
 *
 * @package Drupal\system_tags\SystemTagListBuilder
 */
class SystemTagListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'label' => $this->t('Label'),
      'id' => $this->t('Machine name'),
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [
      'label' => $entity->label(),
      'id' => $entity->id(),
    ];

    return $row + parent::buildRow($entity);
  }

}
