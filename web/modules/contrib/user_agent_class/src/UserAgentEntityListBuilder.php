<?php

namespace Drupal\user_agent_class;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of User agent entity entities.
 */
class UserAgentEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('User agent');
    $header['class'] = $this->t('Class in body');
    $header['enableCheck'] = $this->t('Selected');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['class'] = $entity->getClassName();
    $row['enableCheck'] = empty($entity->getEnableCheck()) ? 'No' : 'Yes';
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
