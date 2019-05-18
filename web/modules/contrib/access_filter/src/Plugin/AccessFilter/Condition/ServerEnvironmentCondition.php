<?php

namespace Drupal\access_filter\Plugin\AccessFilter\Condition;

use Symfony\Component\HttpFoundation\Request;

/**
 * Filter condition using server environment value.
 *
 * @AccessFilterCondition(
 *   id = "env",
 *   description = @Translation("Server environment value."),
 *   examples = {
 *     "- { type: env, key: HTTP_REFERER, value: '/foo/bar' }",
 *     "- { type: env, key: HTTP_USER_AGENT, value: '/(MSIE|Trident)/', regex: 1 }"
 *   }
 * )
 */
class ServerEnvironmentCondition extends ArrayConditionBase {

  /**
   * {@inheritdoc}
   */
  public function isMatched(Request $request) {
    return $this->isMatchedWithArray($_SERVER);
  }

}
