<?php

namespace Drupal\access_filter\Plugin\AccessFilter\Condition;

use Symfony\Component\HttpFoundation\Request;

/**
 * Filter condition using cookie value.
 *
 * @AccessFilterCondition(
 *   id = "cookie",
 *   description = @Translation("Cookie value."),
 *   examples = {
 *     "- { type: cookie, key: foo, value: bar }",
 *     "- { type: cookie, key: foo, value: '/[a-z]+/', regex: 1 }"
 *   }
 * )
 */
class CookieCondition extends ArrayConditionBase {

  /**
   * {@inheritdoc}
   */
  public function isMatched(Request $request) {
    return $this->isMatchedWithArray($_COOKIE);
  }

}
