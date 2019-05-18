<?php

namespace Drupal\cacheflush_ui\Plugin\Action;

use Drupal\cacheflush_ui\CacheflushUIConstantsInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Add menu item.
 *
 * @Action(
 *   id = "cacheflush_menu_action",
 *   label = @Translation("Add menu entry"),
 *   type = "cacheflush"
 * )
 */
class AddMenuCacheflush extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    foreach ($entities as $entity) {
      $this->execute($entity);
    }
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->menu = CacheflushUIConstantsInterface::CACHEFLUSH_MENU;
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $object->access('update', $account, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
