<?php

namespace Drupal\access_filter\Plugin\AccessFilter\Condition;

use Drupal\Core\Path\PathMatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter condition using drupal path.
 *
 * @AccessFilterCondition(
 *   id = "path",
 *   description = @Translation("Drupal path."),
 *   examples = {
 *     "- { type: path, path: /foo/bar }",
 *     "- { type: path, path: '/\/foo\/(bar|baz)/i', regex: 1 }"
 *   }
 * )
 */
class PathCondition extends ConditionBase {

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Creates a new PathCondition object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PathMatcherInterface $path_matcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.matcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $regex = !empty($this->configuration['regex']) ? '<span class="regex">[Regex]</span>' : '';
    return $this->configuration['path'] . $regex;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfiguration(array $configuration) {
    $errors = [];

    if (!isset($configuration['path']) || !strlen($configuration['path'])) {
      $errors[] = $this->t("'@property' is required.", ['@property' => 'path']);
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function isMatched(Request $request) {
    $path = $request->getPathInfo();
    if (empty($this->configuration['regex'])) {
      return $this->pathMatcher->matchPath($path, $this->configuration['path']);
    }
    else {
      return (bool) preg_match($this->configuration['path'], $path);
    }
  }

}
