<?php

namespace Drupal\hp;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Builds the list of protected form entities.
 */
class FormStrategyListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['form_id'] = $this->t('ID');
    $header['regexp'] = $this->t('Regular expression');
    $header['plugin'] = $this->t('Plugin');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['regexp'] = $entity->getRegexp();
    $row['plugin'] = $entity->getPluginId();

    if (empty($row['regexp'])) {
      $row['regexp'] = '-';
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No protected forms have been defined yet.');
    return $build;
  }

}
