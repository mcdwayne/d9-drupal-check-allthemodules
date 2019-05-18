<?php

namespace Drupal\address_cn\Plugin\GraphQL\Types;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\graphql\Plugin\GraphQL\Types\TypePluginBase;

/**
 * GraphQL type for AddressSubdivision.
 *
 * @GraphQLType(
 *   id = "address_subdivision",
 *   name = "AddressSubdivision",
 * )
 */
class AddressSubdivision extends TypePluginBase {

  use DependencySerializationTrait;

}
