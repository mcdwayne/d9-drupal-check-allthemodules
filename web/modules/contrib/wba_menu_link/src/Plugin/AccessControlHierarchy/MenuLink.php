<?php

namespace Drupal\wba_menu_link\Plugin\AccessControlHierarchy;

use Drupal\workbench_access\Plugin\AccessControlHierarchy\Menu;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a hierarchy based on a Menu and a menu link field.
 *
 * @AccessControlHierarchy(
 *   id = "menu_link",
 *   module = "menu_link",
 *   base_entity = "menu",
 *   label = @Translation("Menu (Menu link field based)"),
 *   description = @Translation("Uses a menu as an access control hierarchy, based on a menu link field.")
 * )
 */
class MenuLink extends Menu {

  /**
   * @inheritdoc
   */
  public function getFields($entity_type, $bundle, $parents) {
    $list = [];
    $query = \Drupal::entityQuery('field_config')
      ->condition('status', 1)
      ->condition('entity_type', $entity_type)
      ->condition('bundle', $bundle)
      ->condition('field_type', 'menu_link')
      ->sort('label')
      ->execute();
    $fields = \Drupal::entityTypeManager()->getStorage('field_config')->loadMultiple(array_keys($query));
    foreach ($fields as $id => $field) {
      $list[$field->getName()] = $field->label();
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function alterOptions($field, WorkbenchAccessManagerInterface $manager, array $user_sections = []) {
    $element = $field;
    $menu_check = [];
    foreach ($element['widget'][0]['menu_parent']['#options'] as $id => $data) {
      // The menu value here prepends the menu name. Remove that.
      $parts = explode(':', $id);
      $menu = array_shift($parts);
      $sections = [implode(':', $parts)];
      // Remove unusable elements, except the existing parent.
      if ((!empty($element['widget'][0]['menu_parent']['#default_value']) && $id != $element['widget'][0]['menu_parent']['#default_value']) && empty($manager->checkTree($sections, $user_sections))) {
        unset($element['widget'][0]['menu_parent']['#options'][$id]);
      }
      // Check for the root menu item.
      if (!isset($menu_check[$menu]) && isset($element['widget'][0]['menu_parent']['#options'][$menu . ':'])) {
        if (empty($manager->checkTree([$menu], $user_sections))) {
          unset($element['widget'][0]['menu_parent']['#options'][$menu . ':']);
        }
        $menu_check[$menu] = TRUE;
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityValues(EntityInterface $entity, $field) {
    $ids = [];
    $item_list = $entity->get($field);
    if (!empty($item_list)) {
      foreach ($item_list as $delta => $item) {
        $ids[] = $item->getMenuPluginId();
      }
    }
    return $ids;
  }

}
