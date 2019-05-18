<?php
/**
 * @file
 * Contains \Drupal\collect\Query\QueryEvaluatorHelperInterface.
 */

namespace Drupal\collect\Query;

/**
 * Interface for a helper of DelegatingQueryEvaluator.
 *
 * @see \Drupal\collect\Query\DelegatingQueryEvaluator
 */
interface QueryEvaluatorHelperInterface {

  /**
   * Returns the data at the given path in the data.
   *
   * @param mixed $data
   *   Parsed data.
   * @param string[] $path
   *   The path to follow.
   *
   * @returns mixed
   *   The data at the path in the array.
   */
  public function resolveQueryPath($data, array $path);

}
