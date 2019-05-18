<?php

namespace Drupal\graphql_entity_definitions\Plugin\GraphQL\Fields\EntityDefinition\Fields;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * @GraphQLField(
 *   id = "entity_definition_field_default_value",
 *   secure = true,
 *   name = "defaultValue",
 *   type = "String",
 *   parents = {"EntityDefinitionField"}
 * )
 */
class DefaultValue extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    /** @var \Drupal\field\Entity\FieldConfig $value */
    $default_value = $value->getDefaultValueLiteral();
    if (is_array($default_value)) {
      if ($value->getType() === 'text_long') {
        yield $default_value[0]['value'];
      }
    }
    else {
      yield $default_value;
    }

    yield NULL;
  }
}
