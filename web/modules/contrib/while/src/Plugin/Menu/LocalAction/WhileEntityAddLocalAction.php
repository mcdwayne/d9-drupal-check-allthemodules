<?php

namespace Drupal\white_label_entity\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a local action plugin with a dynamic title.
 */
class WhileEntityAddLocalAction extends LocalActionDefault {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    $entity_type = \Drupal::entityTypeManager()->getDefinition('while_entity');
    return $this->t('Add %entity_name', ['%entity_name' => $entity_type->getSingularLabel()]);
  }

}
