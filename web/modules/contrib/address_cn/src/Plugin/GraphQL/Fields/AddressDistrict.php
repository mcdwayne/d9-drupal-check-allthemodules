<?php

namespace Drupal\address_cn\Plugin\GraphQL\Fields;

use Drupal\address\AddressInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * Address district (dependent_locality).
 *
 * @GraphQLField(
 *   id = "address_district",
 *   name = "district",
 *   type = "AddressSubdivision",
 *   parents = { "Address" },
 *   secure = true,
 * )
 */
class AddressDistrict extends AddressSubdivisionFieldBase {

  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    if ($value instanceof AddressInterface && $code = $value->getDependentLocality()) {
      $parents = [$value->getCountryCode(), $value->getAdministrativeArea(), $value->getLocality()];
      yield $this->resolveSubdivision($code, $parents, $value->getLocale());
    }
  }

}
