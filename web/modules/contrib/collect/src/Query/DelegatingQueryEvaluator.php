<?php
/**
 * @file
 * Contains \Drupal\collect\Query\DelegatingQueryEvaluator.
 */

namespace Drupal\collect\Query;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;

/**
 * Standard query evaluator, delegating path & operations handling to a helper.
 */
class DelegatingQueryEvaluator implements QueryEvaluatorInterface {

  /**
   * The separator between the path and the operations in a query.
   *
   * @var string
   */
  const QUERY_SEPARATOR = '?';

  /**
   * The separator between segments in the path of a query.
   *
   * @var string
   */
  const PATH_SEPARATOR = '.';

  /**
   * The separator between operations.
   *
   * @var string
   */
  const OPERATIONS_SEPARATOR = '+';

  /**
   * The query evaluator helper.
   *
   * @var \Drupal\collect\Query\QueryEvaluatorHelperInterface
   */
  protected $helper;

  /**
   * Constructs a new DelegatingQueryEvaluator object.
   *
   * @param \Drupal\collect\Query\QueryEvaluatorHelperInterface $helper
   *   An object with specific query method implementations.
   */
  public function __construct(QueryEvaluatorHelperInterface $helper) {
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate($data, $query) {
    list($path, $operations) = explode(static::QUERY_SEPARATOR, $query . static::QUERY_SEPARATOR);
    $resolved_value = $data;
    if ($path != '') {
      $resolved_value = $this->helper->resolveQueryPath($data, explode(static::PATH_SEPARATOR, $path));
    }
    $operated_value = $this->applyQueryOperations($resolved_value, $operations);
    return $operated_value;
  }

  /**
   * Apply operations to a piece of data.
   *
   * @param mixed $value
   *   Data as parsed by model plugin and possibly subaddressed by a path.
   * @param string $operations
   *   An expression of operations, as read from the query.
   *
   * @todo Define the structure of $operations. For now, it is a flat list of names of commands to apply subsequently.
   * @todo Let helper define available operations.
   *
   * @return mixed
   *   The result of the operations.
   */
  public function applyQueryOperations($value, $operations) {
    // Apply operations on the value, passing the output of each operation as
    // input to the next.
    foreach (explode(static::OPERATIONS_SEPARATOR, $operations) as $operation) {
      switch ($operation) {
        case 'filter':
          if (!is_string($value)) {
            throw new \InvalidArgumentException(SafeMarkup::format('Value to filter must be string, is actually @type', ['@type' => gettype($value)]));
          }
          $value = Xss::filter($value);
          break;
      }
    }
    return $value;
  }

}
