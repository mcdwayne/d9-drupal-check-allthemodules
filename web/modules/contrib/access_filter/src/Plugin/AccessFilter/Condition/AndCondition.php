<?php

namespace Drupal\access_filter\Plugin\AccessFilter\Condition;

use Symfony\Component\HttpFoundation\Request;

/**
 * Filter condition that chains conditions with 'AND'.
 *
 * @AccessFilterCondition(
 *   id = "and",
 *   description = @Translation("Chain conditions with AND."),
 *   examples = {
 *     "- { type: and, conditions: [{ type: path, path: /foo/bar }, { type: path, path: /foo/baz }] }"
 *   }
 * )
 */
class AndCondition extends ChainConditionBase {

  /**
   * {@inheritdoc}
   */
  public function isMatched(Request $request) {
    foreach ($this->configuration['conditions'] as $condition) {
      $instance = $this->createPluginInstance($condition);
      if ($instance && !$instance->isMatched($request)) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
