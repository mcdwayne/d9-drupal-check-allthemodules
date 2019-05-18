<?php

namespace Drupal\address_cn\Plugin\GraphQL\Fields;

use Drupal\address\AddressInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * Address province (administrative area).
 *
 * @GraphQLField(
 *   id = "address_province",
 *   name = "province",
 *   type = "AddressSubdivision!",
 *   parents = { "Address" },
 *   secure = true,
 * )
 */
class AddressProvince extends AddressSubdivisionFieldBase {

  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    if ($value instanceof AddressInterface && $code = $value->getAdministrativeArea()) {
      $parents = [$value->getCountryCode()];
      yield $this->resolveSubdivision($code, $parents, $value->getLocale());
    }
  }

}
