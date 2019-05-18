<?php

namespace Drupal\graphql_jwt\Plugin\GraphQL\Fields;

use Drupal\graphql\GraphQL\Cache\CacheableValue;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * The token JWT.
 *
 * @GraphQLField(
 *   secure = true,
 *   parents = {"JwtTokenResult"},
 *   id = "graphql_jwt_token",
 *   name = "jwt",
 *   type = "String"
 * )
 */
class JwtToken extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    yield (new CacheableValue($value['jwt']))->mergeCacheMaxAge(0);
  }

}
