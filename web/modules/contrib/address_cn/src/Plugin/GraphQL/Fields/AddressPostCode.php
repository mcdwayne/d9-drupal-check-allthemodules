<?php

namespace Drupal\address_cn\Plugin\GraphQL\Fields;

use Drupal\address\AddressInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * Address post code.
 *
 * @GraphQLField(
 *   id = "address_post_code",
 *   name = "postCode",
 *   type = "String",
 *   parents = { "Address" },
 *   secure = true,
 * )
 */
class AddressPostCode extends FieldPluginBase {

  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    if ($value instanceof AddressInterface) {
      yield $value->getPostalCode();
    }
  }

}
