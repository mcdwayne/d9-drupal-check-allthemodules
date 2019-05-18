<?php

namespace Drupal\access_filter\Plugin\AccessFilter\Condition;

use Symfony\Component\HttpFoundation\Request;

/**
 * Filter condition using session value.
 *
 * @AccessFilterCondition(
 *   id = "session",
 *   description = @Translation("Session value."),
 *   examples = {
 *     "- { type: session, key: foo, value: bar }",
 *     "- { type: session, key: foo, value: '/[a-z]+/', regex: 1 }"
 *   }
 * )
 */
class SessionCondition extends ArrayConditionBase {

  /**
   * {@inheritdoc}
   */
  public function isMatched(Request $request) {
    $session = $request->getSession();
    if ($session) {
      return $this->isMatchedWithArray($session->all());
    }
    return FALSE;
  }

}
