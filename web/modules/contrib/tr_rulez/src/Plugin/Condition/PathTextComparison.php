<?php

namespace Drupal\tr_rulez\Plugin\Condition;

use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesConditionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Path text comparison' condition for path matching.
 *
 * @Condition(
 *   id = "rules_path_text_comparison",
 *   label = @Translation("Path text comparison"),
 *   category = @Translation("Path"),
 *   context = {
 *     "operator" = @ContextDefinition("string",
 *       label = @Translation("Operator"),
 *       description = @Translation("The comparison operator. One of 'contains', 'starts', 'ends', or 'regex'. Defaults to 'contains'."),
 *       list_options_callback = "operatorOptions",
 *       multiple = FALSE,
 *       required = TRUE,
 *       default_value = "contains"
 *     ),
 *     "match" = @ContextDefinition("string",
 *       label = @Translation("Matching text"),
 *       description = @Translation("Specify the text to search for in the path. When using 'regex' as the operator do NOT include the delimiters (typically '/') at the beginning and end of the pattern.")
 *     ),
 *   }
 * )
 */
class PathTextComparison extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * Constructs a PathTextComparison object.
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
   * Returns an array of text comparison operator options.
   *
   * @return array
   *   An array of comparison operators with key matching values.
   */
  public function operatorOptions() {
    return [
      'contains' => 'contains',
      'starts' => 'starts',
      'ends' => 'ends',
      'regex' => 'regex',
    ];
  }

  /**
   * Compare the path with the provided text.
   *
   * @param string $operator
   *   Text comparison operator. One of:
   *   - contains: (default) Evaluate if the path contains $match.
   *   - starts: Evaluate if the path starts with $match.
   *   - ends: Evaluate if the path ends with $match.
   *   - regex: Evaluate if a regular expression in $match matches the path.
   *   Values that do not match one of these operators default to "contains".
   * @param string $match
   *   The string to be compared against the path.
   *
   * @return bool
   *   TRUE if the system path contains the text.
   */
  protected function doEvaluate($operator, $match) {
    $current_path = $this->currentPathStack->getPath();

    $operator = $operator ? $operator : 'contains';
    switch ($operator) {
      case 'starts':
        return mb_strpos($current_path, $match) === 0;

      case 'ends':
        return mb_strrpos($current_path, $match) === (mb_strlen($current_path) - mb_strlen($match));

      case 'regex':
        return (bool) preg_match('/' . str_replace('/', '\\/', $match) . '/', $current_path);

      case 'contains':
      default:
        // Default operator "contains".
        return mb_strpos($current_path, $match) !== FALSE;
    }
  }

}
