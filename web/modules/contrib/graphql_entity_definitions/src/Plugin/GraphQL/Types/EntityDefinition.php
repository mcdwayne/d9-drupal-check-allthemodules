<?php

namespace Drupal\graphql_entity_definitions\Plugin\GraphQL\Types;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\graphql\Plugin\GraphQL\Types\TypePluginBase;
use GraphQL\Type\Definition\ResolveInfo;
use Drupal\graphql\GraphQL\Execution\ResolveContext;

/**
 * An entity definition type.
 *
 * @GraphQLType(
 *   id = "entity_definition_type",
 *   name = "EntityDefinitionType",
 *   interfaces = {"EntityDefinition"}
 * )
 */
class EntityDefinition extends TypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function applies($object, ResolveContext $context, ResolveInfo $info) {
    return $object instanceof ContentEntityType;
  }

}
