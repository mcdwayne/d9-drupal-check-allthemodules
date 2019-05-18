<?php

namespace Drupal\sdk\Entity\ListBuilder\Sdk;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;

/**
 * Default list builder for overview page.
 */
class DefaultListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return $this->properties() + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];

    foreach ($this->properties() as $property => $label) {
      $row[$property] = empty($entity->{$property}) ? '-' : $entity->{$property};
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * Returns conformity of entity properties and their labels.
   *
   * @return string[]
   *   Conformity of entity properties and their labels.
   */
  private function properties() {
    return [
      'label' => $this->t('Label'),
    ];
  }

}
