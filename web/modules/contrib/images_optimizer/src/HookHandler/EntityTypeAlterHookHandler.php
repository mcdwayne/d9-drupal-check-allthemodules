<?php

namespace Drupal\images_optimizer\HookHandler;

use Drupal\images_optimizer\Entity\ImagesOptimizerImageStyle;

/**
 * Hook handler for the entity_type_alter() hook.
 *
 * @package Drupal\images_optimizer\HookHandler
 */
class EntityTypeAlterHookHandler {

  /**
   * Try to substitute the current "image_style" entity class with ours.
   *
   * @param array $entity_types
   *   The entity types.
   *
   * @return bool
   *   TRUE if the substitution was successful, FALSE otherwise.
   */
  public function process(array &$entity_types) {
    if (!isset($entity_types['image_style'])) {
      return FALSE;
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $image_style_config_entity_type */
    $image_style_config_entity_type = $entity_types['image_style'];
    $image_style_config_entity_type->setClass(ImagesOptimizerImageStyle::class);

    return TRUE;
  }

}
