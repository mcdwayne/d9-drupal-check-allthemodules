<?php

namespace Drupal\crm_core_activity;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class ActivityTypeListBuilder.
 *
 * List builder for the activity type entity.
 *
 * @package Drupal\crm_core_activity
 * @see \Drupal\crm_core_activity\Entity\ActivityType
 */
class ActivityTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];

    $header['title'] = $this->t('Name');

    $header['description'] = [
      'data' => $this->t('Description'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];

    $row['title'] = [
      'data' => $this->getLabel($entity),
      'class' => ['menu-label'],
    ];

    $row['description'] = Xss::filterAdmin($entity->description);

    return $row + parent::buildRow($entity);
  }

}
