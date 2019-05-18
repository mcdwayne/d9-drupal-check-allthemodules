<?php

namespace Drupal\entityqueryapi\QueryBuilder;

//use Drupal\entityqueryapi\QueryBuilder\ExistsOption;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class Parser.
 *
 * This class provides methods for extracting options used to build an
 * EntityQuery from a request.
 *
 * @package Drupal\entityqueryapi\QueryBuilder
 */
class Parser {

  /**
   * Extracts an array of QueryOptions from a request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   An HTTP Request object.
   *
   * @return \Drupal\entityqueryapi\QueryBuilder\QueryOption[]
   */
  public static function getQueryOptions(Request $request) {
    return static::getOptions(static::getQueryParams($request));
  }

  /**
   * Returns query paramaters from an HTTP request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   An HTTP Request object.
   *
   * @return \Symfony\Component\HttpFoundation\ParameterBag
   */
  protected static function getQueryParams(Request $request) {
    return $request->query;
  }

  /**
   * Creates and returns QueryOptions from a ParameterBag.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $params
   *
   * @return \Drupal\entityqueryapi\QueryBuilder\QueryOption[]
   */
  protected static function getOptions(ParameterBag $params) {
    $option_keys = static::getValidOptionKeys($params->keys());

    $options = array_merge(
      static::getSorts($option_keys['sorts'], $params),
      static::getExists($option_keys['exists'], $params),
      static::getGroups($option_keys['groups'], $params),
      static::getRange($option_keys['range'], $params),
      static::getConditions($option_keys['conditions'], $params)
    );

    return $options;
  }

  /**
   * Gets all valid query param keys for building QueryOptions.
   *
   * @param string[] $keys
   *   An array of query parameter keys to validate.
   *
   * @return array
   *  An array of valid keys, grouped by their type.
   */
  protected static function getValidOptionKeys(array $keys) {
    // This reducer validates a key and appends it to the appropriate group of
    // keys.
    $reduce_keys = function ($option_keys, $key) {
      if (preg_match('/^condition_\d+$/', $key))  $option_keys['conditions'][] = $key;
      if (preg_match('/^sort_\d+$/', $key))       $option_keys['sorts'][] = $key;
      if (preg_match('/^exists_\d+$/', $key))     $option_keys['exists'][] = $key;
      if (preg_match('/^group_\d+$/', $key))      $option_keys['groups'][] = $key;
      if (preg_match('/^range$/', $key))          $option_keys['range'][] = $key;
      return $option_keys;
    };

    $option_keys = array_reduce($keys, $reduce_keys, array(
      'conditions' => array(),
      'sorts' => array(),
      'exists' => array(),
      'groups' => array(),
      'range' => array(),
    ));

    return $option_keys;
  }

  /**
   * Given a set of valid keys, this will return an array of QueryOptions.
   *
   * @param array $keys
   *   An array of valid keys.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $params
   *   A ParameterBag with the values needed to build a QueryOption.
   *
   * @return \Drupal\entityqueryapi\QueryBuilder\GroupOption[]
   *  An array of GroupOptions.
   */
  protected static function getGroups($keys, $params) {
    return array_map(function ($group) {
      $conjunction = (isset($group['param']['conjunction'])) ? $group['param']['conjunction'] : 'AND';
      $parentGroup = (isset($group['param']['group'])) ? $group['param']['group'] : NULL;
      return new GroupOption($group['key'], $conjunction, $parentGroup);
    }, array_filter(
      static::getParamsByKey($params, $keys),
      [ __CLASS__, 'validateGroupParam' ])
    );
  }

  /**
   * Given a query parameter, ensure that it will produce a valid GroupOption.
   *
   * @param mixed $param
   *   A parameter to validate.
   *
   * @return bool
   *  Whether the parameter can produce a valid GroupOption.
   */
  protected static function validateGroupParam($param) {
    return (
      // If a conjunction is set, it must be AND or OR.
      (!isset($param['param']['conjunction']) || in_array($param['param']['conjunction'], array(
        'AND',
        'OR',
      )))
      // If a group is set, it must be a valid id for a group.
      && (!isset($param['param']['group']) || preg_match('/^group_\d+$/', $param['param']['group']))
    );
  }

  /**
   * Given a set of valid keys, this will return an array of QueryOptions.
   *
   * @param array $keys
   *   An array of valid keys.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $params
   *   A ParameterBag with the values needed to build a QueryOption.
   *
   * @return \Drupal\entityqueryapi\QueryBuilder\QueryOption[]
   *  An array of QueryOptions.
   */
  protected static function getSorts($keys, $params) {
    return array_map(function ($sort) {
      $field = (isset($sort['param']['field'])) ? $sort['param']['field'] : NULL;
      $direction = (isset($sort['param']['direction'])) ? $sort['param']['direction'] : 'ASC';
      $langcode = (isset($sort['param']['langcode'])) ? $sort['param']['langcode'] : NULL;
      return new SortOption($sort['key'], $field, $direction, $langcode);
    }, array_filter(
      static::getParamsByKey($params, $keys),
      [ __CLASS__, 'validateSortParam' ])
    );
  }

  /**
   * Given a query parameter, ensure that it will produce a valid SortOption.
   *
   * @param mixed $param
   *   A parameter to validate.
   *
   * @return bool
   *  Whether the parameter can produce a valid SortOption.
   */
  protected static function validateSortParam($param) {
    return (
      // field must be set
      isset($param['param']['field']) &&
      // if direction is set, it must be ASC or DESC
      (!isset($param['param']['direction']) || in_array($param['param']['direction'], array(
        'ASC',
        'DESC',
      )))
    );
  }

  /**
   * Given a set of valid keys, this will return an array of QueryOptions.
   *
   * @param array $keys
   *   An array of valid keys.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $params
   *   A ParameterBag with the values needed to build a QueryOption.
   *
   * @return \Drupal\entityqueryapi\QueryBuilder\QueryOption[]
   *  An array of QueryOptions.
   */
  protected static function getExists($keys, $params) {
    return array_map(function ($option) {
      $field = $option['param']['field'];
      $exist = $option['param']['condition'];
      $langcode = (isset($option['param']['langcode'])) ? $option['param']['langcode'] : NULL;
      $group = (isset($option['param']['group'])) ? $option['param']['group'] : NULL;

      switch ($exist) {
      case 'TRUE':
        $exist = TRUE;
        break;
      case 'FALSE':
        $exist = FALSE;
        break;
      }

      return new ExistsOption($option['key'], $field, $exist, $langcode, $group);
    }, array_filter(
      static::getParamsByKey($params, $keys),
      [ __CLASS__, 'validateExistParam' ])
    );
  }

  /**
   * Given a query parameter, ensure that it will produce a valid ExistOption.
   *
   * @param mixed $param
   *   A parameter to validate.
   *
   * @return bool
   *  Whether the parameter can produce a valid ExistOption.
   */
  protected static function validateExistParam($param) {
    return (
      isset($param['param']['field'])
      && isset($param['param']['condition'])
      && in_array($param['param']['condition'], array(
        'TRUE',
        'FALSE',
      )) && (
        !isset($param['param']['group']) || preg_match('/^group_\d+$/', $param['param']['group'])
      )
    );
  }

  /**
   * Given a set of valid keys, this will return an array of QueryOptions.
   *
   * @param array $keys
   *   An array of valid keys.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $params
   *   A ParameterBag with the values needed to build a QueryOption.
   *
   * @return \Drupal\entityqueryapi\QueryBuilder\QueryOption[]
   *  An array of QueryOptions.
   */
  protected static function getRange($keys, $params) {
    return array_map(function ($param) {
      $start = (isset($param['param']['start'])) ? $param['param']['start'] : NULL;
      $length = (isset($param['param']['length'])) ? $param['param']['length'] : NULL;
      return new RangeOption($param['key'], $start, $length);
    }, array_filter(
      static::getParamsByKey($params, $keys),
      [ __CLASS__, 'validateRangeParam' ])
    );
  }

  /**
   * Given a query parameter, ensure that it will produce a valid RangeOption.
   *
   * @param mixed $param
   *   A parameter to validate.
   *
   * @return bool
   *  Whether the parameter can produce a valid RangeOption.
   */
  protected static function validateRangeParam($param) {
    // At the least, start and/or length must be defined.
    if (!isset($param['param']['start']) && !isset($param['param']['length'])) return FALSE;

    // If start is defined, it must be numeric.
    if (isset($param['param']['start'])) {
      if (!preg_match('/^\d+$/', $param['param']['start'])) return FALSE;
    }

    // If length is defined, it must be numeric.
    if (isset($param['param']['length'])) {
      if (!preg_match('/^\d+$/', $param['param']['length'])) return FALSE;
    }

    // If we made it this far, the range is fine.
    return TRUE;
  }

  /**
   * Given a set of valid keys, this will return an array of QueryOptions.
   *
   * @param array $keys
   *   An array of valid keys.
   *
   * @param \Symfony\Component\HttpFoundation\ParameterBag $params
   *   A ParameterBag with the values needed to build a QueryOption.
   *
   * @return \Drupal\entityqueryapi\QueryBuilder\QueryOption[]
   *  An array of QueryOptions.
   */
  protected static function getConditions($keys, $params) {
    $map_operator_func = function ($param) {
      $operator_dict = array(
        'EQ' => '=',
        'NOTEQ' => '<>',
        'GT' => '>',
        'GTEQ' => '>=',
        'LT' => '<',
        'LTEQ' => '<=',
      );

      if (key_exists($param['param']['operator'], $operator_dict)) {
        $param['param']['operator'] = $operator_dict[$param['param']['operator']];
      }

      return $param;
    };

    $conditions = array_filter(
      static::getParamsByKey($params, $keys),
      [ __CLASS__, 'validateConditionParam' ]
    );

    $conditions = array_map(
      $map_operator_func,
      $conditions
    );

    return array_map(function ($condition) {
      $field = $condition['param']['field'];
      $value = $condition['param']['value'];
      $operator = $condition['param']['operator'];
      $langcode = (isset($condition['param']['langcode'])) ? $condition['param']['langcode'] : NULL;
      $group = (isset($condition['param']['group'])) ? $condition['param']['group'] : NULL;

      return new ConditionOption(
        $condition['key'],
        $field,
        $value,
        $operator,
        $langcode,
        $group
      );
    }, $conditions);
  }

  /**
   * Given a query parameter, ensure that it will produce a valid ConditionOption.
   *
   * @param mixed $param
   *   A parameter to validate.
   *
   * @return bool
   *  Whether the parameter can produce a valid ConditionOption.
   */
  protected static function validateConditionParam($param) {
    return (
      isset($param['param']['field']) &&
      isset($param['param']['value']) &&
      isset($param['param']['operator']) &&
      in_array($param['param']['operator'], array(
        'EQ',
        'NOTEQ',
        'GT',
        'GTEQ',
        'LT',
        'LTEQ',
        'STARTS_WITH',
        'CONTAINS',
        'ENDS_WITH',
        'IN',
        'NOT_IN',
        'BETWEEN',
      )) && (
        !isset($param['param']['group']) || preg_match('/^group_\d+$/', $param['param']['group'])
      )
    );
  }

  /**
   * Extracts all parameter values given an array of keys.
   *
   * @param Symfony\Component\HttpFoundation\ParameterBag $params
   *   A ParameterBag from an HTTP request.
   * @param string[] $keys
   *   An array of keys which will be used to get parameters.
   *
   * @return array
   *  An array of parameters
   */
  protected static function getParamsByKey($params, $keys) {
    return array_map(function ($key) use ($params) {
      return array(
        'key'   => $key,
        'param' => $params->get($key),
      );
    }, $keys);
  }
}
