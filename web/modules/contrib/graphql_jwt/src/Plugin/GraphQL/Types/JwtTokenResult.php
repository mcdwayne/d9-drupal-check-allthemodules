<?php

namespace Drupal\graphql_jwt\Plugin\GraphQL\Types;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Types\TypePluginBase;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * JWT token result type.
 *
 * @GraphQLType(
 *   id = "graphql_jwt_token_result",
 *   name = "JwtTokenResult",
 * )
 */
class JwtTokenResult extends TypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function applies($object, ResolveContext $context, ResolveInfo $info) {
    return $object['type'] == 'JwtTokenResult';
  }

}
