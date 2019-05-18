<?php

namespace Drupal\address_cn\Plugin\GraphQL\InputTypes;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\graphql\Plugin\GraphQL\InputTypes\InputTypePluginBase;

/**
 * Address input type.
 *
 * @GraphQLInputType(
 *   id = "address_input",
 *   name = "AddressInput",
 *   fields = {
 *     "contact" = "String!",
 *     "province" = "String!",
 *     "city" = "String!",
 *     "district" = "String",
 *     "streetAddress" = "String!",
 *   },
 * )
 *
 * Note: the 'district' is nullable.
 */
class AddressInput extends InputTypePluginBase {

  use DependencySerializationTrait;

}
