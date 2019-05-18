<?php

namespace Drupal\address_cn\Plugin\GraphQL\Fields;

use Drupal\address\AddressInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * Address city (locality).
 *
 * @GraphQLField(
 *   id = "address_city",
 *   name = "city",
 *   type = "AddressSubdivision!",
 *   parents = { "Address" },
 *   secure = true,
 * )
 */
class AddressCity extends AddressSubdivisionFieldBase {

  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    if ($value instanceof AddressInterface && $code = $value->getLocality()) {
      $parents = [$value->getCountryCode(), $value->getAdministrativeArea()];
      yield $this->resolveSubdivision($code, $parents, $value->getLocale());
    }
  }

}
