<?php

namespace Drupal\graphql_entity_definitions\Plugin\GraphQL\Fields\EntityDefinition\Fields;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * @GraphQLField(
 *   id = "entity_definition_field_multiple",
 *   secure = true,
 *   name = "multiple",
 *   type = "Boolean",
 *   parents = {"EntityDefinitionField"}
 * )
 */
class Multiple extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    if ($value instanceof BaseFieldDefinition) {
      /** @var \Drupal\Core\Field\BaseFieldDefinition $value */
      yield $value->isMultiple();
    }
    elseif ($value instanceof FieldConfig) {
      /** @var \Drupal\field\Entity\FieldConfig $value */
      yield $value->isList();
    }
    else {
      yield NULL;
    }
  }

}
