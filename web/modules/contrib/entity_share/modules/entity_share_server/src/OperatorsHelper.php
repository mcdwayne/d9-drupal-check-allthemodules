<?php

namespace Drupal\entity_share_server;

/**
 * Defines the Operators helper class.
 */
class OperatorsHelper {

  /**
   * Helper function to get the operator options.
   *
   * @return array
   *   An array of options.
   */
  public static function getOperatorOptions() {
    return [
      '=' => '=',
      '<' => '<',
      '>' => '>',
      '<>' => '<>',
      'IN' => 'IN',
      'NOT IN' => 'NOT IN',
      'IS NULL' => 'IS NULL',
      'IS NOT NULL' => 'IS NOT NULL',
      'CONTAINS' => 'CONTAINS',
      'BETWEEN' => 'BETWEEN',
    ];
  }

  /**
   * Helper function to get the stand alone operators.
   *
   * Operators that do not require a value to be entered.
   *
   * @return array
   *   An array of options.
   */
  public static function getStandAloneOperators() {
    return [
      'IS NULL' => 'IS NULL',
      'IS NOT NULL' => 'IS NOT NULL',
    ];
  }

  /**
   * Helper function to get the multiple values operators.
   *
   * Operators that allow to have multiple values entered.
   *
   * @return array
   *   An array of options.
   */
  public static function getMultipleValuesOperators() {
    return [
      'IN' => 'IN',
      'NOT IN' => 'NOT IN',
      'BETWEEN' => 'BETWEEN',
    ];

    // TODO: The following operators are marked as multiple values in JSONAPI
    // documentation https://www.drupal.org/docs/8/modules/json-api/collections-filtering-sorting-and-paginating
    // But it does not work. See why.
    // '=' => '=',
    // '<' => '<',
    // '>' => '>',
    // '<>' => '<>',.
  }

}
