<?php

namespace Drupal\theme_change\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of ThemeChange.
 */
class ThemeChangeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['type'] = $this->t('Type');
    $header['path'] = $this->t('Path');
    $header['route'] = $this->t('Route');
    $header['theme'] = $this->t('Theme');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['type'] = $entity->get_type();
    if ($entity->get_path()) {
      $row['path'] = $entity->get_path();
    }
    else {
      $row['path'] = '-';
    }
    if ($entity->get_route()) {
      $row['route'] = $entity->get_route();
    }
    else {
      $row['route'] = '-';
    }
    $theme_name = \Drupal::service('theme_handler')->getName($entity->get_theme());
    $row['theme'] = $theme_name;
    return $row + parent::buildRow($entity);
  }

}
