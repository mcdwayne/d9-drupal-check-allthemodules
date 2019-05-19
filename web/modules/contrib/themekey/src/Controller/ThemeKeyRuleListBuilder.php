<?php

/**
 * @file
 * Contains Drupal\themekey\Controller\ThemeKeyRuleListBuilder.
 */

namespace Drupal\themekey\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of ThemeKeyRule.
 */
class ThemeKeyRuleListBuilder extends ConfigEntityListBuilder
{

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('ThemeKey Rule');
    $header['property'] = $this->t('Property');
    $header['key'] = $this->t('Key');
    $header['operator'] = $this->t('Operator');
    $header['value'] = $this->t('Value');
    $header['theme'] = $this->t('Theme');
    return $header + parent::buildHeader();
  }

  /**
  * {@inheritdoc}
  */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['property'] = $entity->property();
    $row['key'] = $entity->key();
    $row['operator'] = $entity->operator();
    $row['value'] = $entity->value();
    $row['theme'] = $entity->theme();
    return $row + parent::buildRow($entity);
  }
}
