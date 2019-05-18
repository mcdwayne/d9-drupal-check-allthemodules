<?php

namespace Drupal\access_filter\Plugin\AccessFilter\Condition;

use Symfony\Component\HttpFoundation\Request;

/**
 * Filter condition that chains conditions with 'OR'.
 *
 * @AccessFilterCondition(
 *   id = "or",
 *   description = @Translation("Chain conditions with OR."),
 *   examples = {
 *     "- { type: or, conditions: [{ type: path, path: /foo/bar }, { type: path, path: /foo/baz }] }"
 *   }
 * )
 */
class OrCondition extends ChainConditionBase {

  /**
   * {@inheritdoc}
   */
  public function isMatched(Request $request) {
    if (empty($this->configuration['conditions'])) {
      return TRUE;
    }

    foreach ($this->configuration['conditions'] as $condition) {
      $instance = $this->createPluginInstance($condition);
      if ($instance && $instance->isMatched($request)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
