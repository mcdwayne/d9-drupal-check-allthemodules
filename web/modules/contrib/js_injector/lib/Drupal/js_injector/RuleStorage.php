<?php

/**
 * @file
 * Contains \Drupal\js_injector\RuleStorage.
 */

namespace Drupal\js_injector;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for js_injector rules.
 */
class RuleStorage extends ConfigEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function attachLoad(&$queried_entities, $revision_id = FALSE) {
    // Sort the queried roles by their weight.
    uasort($queried_entities, 'Drupal\Core\Config\Entity\ConfigEntityBase::sort');

    parent::attachLoad($queried_entities, $revision_id);
  }
}
