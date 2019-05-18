<?php

namespace Drupal\graphql_entity_definitions\Plugin\GraphQL\Fields\EntityDefinition\Fields;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * @GraphQLField(
 *   id = "entity_definition_field_reference",
 *   secure = true,
 *   name = "isReference",
 *   type = "Boolean",
 *   parents = {"EntityDefinitionField"}
 * )
 */
class Reference extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    if ($value instanceof BaseFieldDefinition) {
      /** @var \Drupal\Core\Field\BaseFieldDefinition $value */
      if ($value->getType() === 'entity_reference') {
        yield TRUE;
      }
      else {
        yield FALSE;
      }
    }
    elseif ($value instanceof FieldConfig) {
      /** @var \Drupal\field\Entity\FieldConfig $value */
      if ($value->getType() === 'entity_reference') {
        yield TRUE;
      }
      else {
        yield FALSE;
      }
    }
    else {
      yield FALSE;
    }
  }

}
