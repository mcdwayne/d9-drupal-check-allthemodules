<?php

namespace Drupal\address_cn\Plugin\GraphQL\Fields;

use Drupal\address\AddressInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * Address phone number (given name).
 *
 * @GraphQLField(
 *   id = "address_phone",
 *   name = "phone",
 *   type = "String!",
 *   parents = { "Address" },
 *   secure = true,
 * )
 */
class AddressPhone extends FieldPluginBase {

  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    if ($value instanceof AddressInterface) {
      yield $value->getGivenName();
    }
  }

}
