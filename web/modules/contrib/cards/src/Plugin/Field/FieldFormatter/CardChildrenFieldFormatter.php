<?php

namespace Drupal\cards\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;
use Drupal\entityreference_view_mode\Plugin\Field\FieldFormatter\EntityReferenceViewModeFormatter;


/**
 * Plugin implementation of the 'field_example_simple_text' formatter.
 *
 * @FieldFormatter(
 *   id = "card_children_field_formatter",
 *   module = "cards",
 *   label = @Translation("Card Children View Formatter"),
 *   field_types = {
 *     "card_children_field_type"
 *   }
 * )
 */
class CardChildrenFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];
//
//    // Get the current node
//      $current_path = \Drupal::service('path.current')->getPath();
//
//      $params = Url::fromUri("internal:" . $current_path)->getRouteParameters();
//      $entity_type = key($params);
//      $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($params[$entity_type]);
//
//      $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
//      $canonical_route = 'entity.' . $entity_type . '.canonical';
//      $menu_link = $menu_link_manager->loadLinksByRoute('entity.' . $entity_type . '.canonical', array($entity_type => $params[$entity_type]));
//
//      $root = array_keys($menu_link)[0];
//      $parameters = \Drupal::menuTree()
//          ->getCurrentRouteMenuTreeParameters('main')
//          ->setRoot($root)->setMinDepth(1)->onlyEnabledLinks();
//      $tree = \Drupal::menuTree()->load('main', $parameters);
//
//      $manipulators = array(
//          array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
//      );
//
//      // Make footer menu tree.
//      $menu_tree         = \Drupal::menuTree();
//      $tree = $menu_tree->transform($tree, $manipulators);
//
//
//      foreach ($tree as $key => $item) {
//          if ($item->link && $item->link->getUrlObject()) {
//            $obj = $item->link->getUrlObject();
//
//        $params = $obj->getRouteParameters();
//              $entity_type = key($params);
//              $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($params[$entity_type]);
//            // load the entity and render it in the card styles.
//              $test = 1;
//
//              if ($entity) {
//
//                  $view_mode = str_replace('node.','',$items[0]->view_mode);
//
//                  $entity->card = $items[0];
//                  $entity->card->target_type = 'node';
//
//                  $elements[$entity->id()] = entity_view($entity, $view_mode);
//              }
//          }
//      }

    return $elements;
  }

}
