<?php

namespace Drupal\query\Common;

/**
 * Class Operator
 *
 * @package Drupal\query\Common
 */
class Operator {
  const TYPE_NULL = 'null';
  const TYPE_NOT_NULL = 'not_null';
  const TYPE_EMPTY = 'empty';
  const TYPE_NOT_EMPTY = 'not_empty';
  const TYPE_EQUIVALENT = 'equivalent';
  const TYPE_EQUALS = 'equals';
  const TYPE_NOT_EQUALS = 'not_equals';
  const TYPE_NOT_EQUIVALENT = 'not_equivalent';
  const TYPE_IN = 'in';
  const TYPE_NOT_IN = 'not_in';
  const TYPE_HAS = 'has';
  const TYPE_HAS_NOT = 'has_not';

  const TYPE_BETWEEN = 'between';
  const TYPE_OUTSIDE = 'outside';

  const TYPE_GREATER_THAN = 'greater_than';
  const TYPE_GREATER_THAN_EQUAL_TO = 'greater_than_equal_to';
  const TYPE_LESS_THAN = 'less_than';
  const TYPE_LESS_THAN_EQUAL_TO = 'less_than_equal_to';
}
