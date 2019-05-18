<?php

namespace Drupal\facebook_flush_cache\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Flush Facebook cache for given node and its url.
 *
 * @Action(
 *   id = "facebook_flush_cache_action",
 *   label = @Translation("Flush Facebook Cache"),
 *   type = "node"
 * )
 */
class FacebookFlushCache extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {

    $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()], ['absolute' => TRUE]);

    if ($url) {

      $url = $url->toString();

      $facebookCacheService = \Drupal::service('facebook_flush_cache.service');

      $facebookCacheService->clearCache($url);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = $object->access('update', $account, TRUE)
      ->allowedIf($account->hasPermission('flush facebook cache'));
    return $return_as_object ? $access : $access->isAllowed();
  }

}
