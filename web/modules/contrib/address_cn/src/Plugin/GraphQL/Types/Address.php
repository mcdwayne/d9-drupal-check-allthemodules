<?php

namespace Drupal\address_cn\Plugin\GraphQL\Types;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\graphql\Plugin\GraphQL\Types\TypePluginBase;

/**
 * GraphQL type for Address.
 *
 * @GraphQLType(
 *   id = "address",
 *   name = "Address",
 * )
 *
 * @see \Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter::viewElement()
 */
class Address extends TypePluginBase {

  use DependencySerializationTrait;

}
