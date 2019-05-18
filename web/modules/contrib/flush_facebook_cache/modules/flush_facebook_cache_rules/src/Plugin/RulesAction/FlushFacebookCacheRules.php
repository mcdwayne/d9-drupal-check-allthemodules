<?php

namespace Drupal\flush_facebook_cache_rules\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Provides custom rules action for facebook cache.
 *
 * @RulesAction(
 *   id = "flush_facebook_cache_rules_action",
 *   label = @Translation("Flush Facebook cache when a node is saved"),
 *   category = @Translation("Flush Facebook cache"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Node")
 *     )
 *   }
 * )
 */
class FlushFacebookCacheRules extends RulesActionBase {

  /**
   * {@inheritdoc}
   */
  protected function doExecute(NodeInterface $node) {

    $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()], ['absolute' => TRUE]);

    if ($url) {

      $url = $url->toString();

      $facebookCacheService = \Drupal::service('facebook_flush_cache.service');

      $facebookCacheService->clearCache($url);
    }

  }

}
