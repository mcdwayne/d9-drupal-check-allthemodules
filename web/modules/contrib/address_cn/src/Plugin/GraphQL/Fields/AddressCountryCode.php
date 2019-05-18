<?php

namespace Drupal\address_cn\Plugin\GraphQL\Fields;

use Drupal\address\AddressInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * Address country code.
 *
 * @GraphQLField(
 *   id = "address_country_code",
 *   name = "countryCode",
 *   type = "String!",
 *   parents = { "Address" },
 *   secure = true,
 * )
 */
class AddressCountryCode extends FieldPluginBase {

  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    if ($value instanceof AddressInterface) {
      yield $value->getCountryCode();
    }
  }

}
