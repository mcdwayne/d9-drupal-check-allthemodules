<?php

namespace Drupal\cmlmigrations\Hook;

/**
 * @file
 * Contains \Drupal\cmlmigrations\Controller\EntityView.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Implements hook_entity_base_field_info().
 */
class EntityBaseFieldInfo extends ControllerBase {

  /**
   * Page Callback.
   */
  public static function hook(EntityTypeInterface $entity_type) {
    if ($entity_type->id() == 'commerce_product_variation') {
      $fields = [];
      $fields['product_uuid'] = BaseFieldDefinition::create('string')
        ->setLabel(t('Product UUID'))
        ->setDescription(t('Product UUID for cmlmigtations search.'));
      return $fields;
    }
  }

}
