<?php

namespace Drupal\tr_rulez\Plugin\Condition;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesConditionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Path contains text' condition.
 *
 * @Condition(
 *   id = "rules_path_contains_text",
 *   label = @Translation("Path contains text"),
 *   category = @Translation("Path"),
 *   context = {
 *     "text" = @ContextDefinition("string",
 *       label = @Translation("Path text"),
 *       description = @Translation("Specify the text to search for in the path.")
 *     ),
 *   }
 * )
 *
 * @todo: Add access callback information from Drupal 7.
 */
class PathContainsText extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * Constructs a PathContainsText object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path_stack
   *   The current path stack service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentPathStack $current_path_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentPathStack = $current_path_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.current')
    );
  }

  /**
   * Check if a path contains a specific text string.
   *
   * @param string $text
   *   The text to check for.
   *
   * @return bool
   *   TRUE if the system path contains the text.
   */
  protected function doEvaluate($text) {
    $current_path = $this->currentPathStack->getPath();
    return (mb_strpos($current_path, $text) !== FALSE);
  }

}
