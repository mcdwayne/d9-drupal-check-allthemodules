<?php

namespace Drupal\graphql_box\Plugin\GraphQL\Fields;

use Drupal\box\Entity\BoxInterface;
use Drupal\graphql_core\GraphQL\FieldPluginBase;
use Youshido\GraphQL\Execution\ResolveInfo;

/**
 * GraphQL field resolving a Box's id.
 *
 * @GraphQLField(
 *   id = "box_machine_name",
 *   secure = true,
 *   name = "boxMachineName",
 *   type = "String",
 *   types = {"Box"}
 * )
 */
class BoxMachineName extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveInfo $info) {
    if ($value instanceof BoxInterface) {
      yield $value->machineName();
    }
  }

}
