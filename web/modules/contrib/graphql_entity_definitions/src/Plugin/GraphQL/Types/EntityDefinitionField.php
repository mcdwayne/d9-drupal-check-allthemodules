<?php

namespace Drupal\graphql_entity_definitions\Plugin\GraphQL\Types;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\graphql\Plugin\GraphQL\Types\TypePluginBase;
use GraphQL\Type\Definition\ResolveInfo;
use Drupal\graphql\GraphQL\Execution\ResolveContext;

/**
 * An entity field type.
 *
 * @GraphQLType(
 *   id = "entity_definition_field_type",
 *   name = "EntityDefinitionFieldType",
 *   interfaces = {"EntityDefinitionField"}
 * )
 */
class EntityDefinitionField extends TypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function applies($object, ResolveContext $context, ResolveInfo $info) {
    return $object instanceof BaseFieldDefinition || $object instanceof FieldConfig;
  }

}
