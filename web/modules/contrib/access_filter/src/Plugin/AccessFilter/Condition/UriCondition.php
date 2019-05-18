<?php

namespace Drupal\access_filter\Plugin\AccessFilter\Condition;

use Symfony\Component\HttpFoundation\Request;

/**
 * Filter condition using request URI.
 *
 * @AccessFilterCondition(
 *   id = "uri",
 *   description = @Translation("Request URI with query parameters."),
 *   examples = {
 *     "- { type: uri, uri: '/foo/bar?param=1' }",
 *     "- { type: uri, uri: '/\/foo\/bar\?param=[0-9]{2}/i', regex: 1 }"
 *   }
 * )
 */
class UriCondition extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $regex = !empty($this->configuration['regex']) ? '<span class="regex">[Regex]</span>' : '';
    return $this->configuration['uri'] . $regex;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfiguration(array $configuration) {
    $errors = [];

    if (!isset($configuration['uri']) || !strlen($configuration['uri'])) {
      $errors[] = $this->t("'@property' is required.", ['@property' => 'uri']);
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function isMatched(Request $request) {
    $uri = $request->getPathInfo();
    $qs = $request->getQueryString();
    if ($qs !== NULL) {
      $uri .= '?' . $qs;
    }

    if (empty($this->configuration['regex'])) {
      return ($uri == $this->configuration['uri']);
    }
    else {
      return (bool) preg_match($this->configuration['uri'], $uri);
    }
  }

}
