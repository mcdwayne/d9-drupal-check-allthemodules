<?php

namespace Drupal\js_manager\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * @file
 * Contains \Drupal\js_manager\Controller\JavascriptManagerListBuilder.
 */

/**
 * Lists Javascript config entities.
 */
class JavascriptManagerListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['type'] = $this->t('Type');
    $header['weight'] = $this->t('Weight');
    $header['scope'] = $this->t('Scope');
    $header['admin_page'] = $this->t('Exclude on admin pages');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['type'] = $entity->getJsType();
    $row['weight'] = $entity->getWeight();
    $row['scope'] = $entity->getScope();
    $row['exclude_admin'] = $entity->excludeAdminLabel();
    return $row + parent::buildRow($entity);
  }

}
