<?php

namespace Drupal\cacheflush_ui\Plugin\Action;

use Drupal\cacheflush_ui\CacheflushUIConstantsInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Publishes a cacheflush.
 *
 * @Action(
 *   id = "cacheflush_publish_action",
 *   label = @Translation("Publish selected content"),
 *   type = "cacheflush"
 * )
 */
class PublishCacheflush extends ActionBase {

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
    $entity->status = CacheflushUIConstantsInterface::CACHEFLUSH_PUBLISHED;
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
