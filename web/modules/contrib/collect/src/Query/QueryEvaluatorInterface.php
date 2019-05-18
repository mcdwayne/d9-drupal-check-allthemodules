<?php
/**
 * @file
 * Contains \Drupal\collect\Model\QueryEvaluatorInterface.
 */

namespace Drupal\collect\Query;

/**
 * Interface for evaluating queries on container data.
 *
 * The object where the query evaluator is instantiated must be aware of the
 * structure of the data that the evaluator is expected to navigate.
 */
interface QueryEvaluatorInterface {

  /**
   * Evaluates the given query to extract a specific piece of data.
   *
   * @param mixed $data
   *   The data to evaluate the query on.
   * @param string $query
   *   The query to evaluate.
   *
   * @return mixed
   *   The data found, or NULL if no data was found.
   */
  public function evaluate($data, $query);

}
