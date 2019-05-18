<?php

namespace Drupal\menu_reference_render\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'menu_reference_render' formatter.
 *
 * @FieldFormatter(
 *   id = "menu_reference_render",
 *   label = @Translation("Rendered menu"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MenuReferenceFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $entity) {
      $menu_name = $entity->get('id');
      $menu_tree = \Drupal::menuTree();

      // Build the typical default set of menu tree parameters.
      $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);

      // Load the tree based on this set of parameters.
      $tree = $menu_tree->load($menu_name, $parameters);

      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ];
      $tree = $menu_tree->transform($tree, $manipulators);

      $elements[] = $menu_tree->build($tree);
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Limit formatter to only menu entity types.
    return ($field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'menu');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    // Set 'view label' operation for menu entity.
    // @see \Drupal\system\MenuAccessControlHandler::checkAccess().
    return $entity->access('view label', NULL, TRUE);
  }

}
