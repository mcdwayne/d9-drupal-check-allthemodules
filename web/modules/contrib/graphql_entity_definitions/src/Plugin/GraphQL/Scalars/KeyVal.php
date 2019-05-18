<?php

namespace Drupal\graphql_entity_definitions\Plugin\GraphQL\Scalars;

use Drupal\graphql\Plugin\GraphQL\Scalars\ScalarPluginBase;

/**
 * @GraphQLScalar(
 *   id = "key_val",
 *   name = "KeyVal"
 * )
 */
class KeyVal extends ScalarPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function serialize($value) {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function parseValue($value) {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function parseLiteral($ast) {
    throw new \LogicException('KeyVals have to be referenced in variables.');
  }

}
