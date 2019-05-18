<?php

namespace Drupal\address_cn_test\Plugin\GraphQL\Fields;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * Node test address
 *
 * @GraphQLField(
 *   id = "node_test_address",
 *   name = "testAddress",
 *   type = "Address!",
 *   parents = { "Node" },
 *   secure = true,
 * )
 */
class NodeTestAddress extends FieldPluginBase {

  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    if ($value instanceof ContentEntityInterface) {
      foreach ($value->get('test_address') as $address) {
        yield $address;
      }
    }
  }

}
