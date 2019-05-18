<?php

namespace Drupal\address_cn\Plugin\GraphQL\Fields;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * Subdivision name.
 *
 * @GraphQLField(
 *   id = "subdivision_name",
 *   name = "name",
 *   type = "String!",
 *   parents = { "AddressSubdivision" },
 *   secure = true,
 * )
 */
class SubdivisionName extends FieldPluginBase {

  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    if (is_array($value)) {
      yield $value['name'];
    }
  }

}
