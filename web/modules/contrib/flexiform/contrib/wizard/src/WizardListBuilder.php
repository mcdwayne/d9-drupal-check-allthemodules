<?php

namespace Drupal\flexiform_wizard;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;

/**
 * List builder for flexiform_wizards.
 */
class WizardListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'id' => $this->t('Wizard'),
      'path' => $this->t('Path'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->toLink(NULL, 'edit-form');
    $row['path'] = $entity->get('path');
    return $row + parent::buildRow($entity);
  }

}
