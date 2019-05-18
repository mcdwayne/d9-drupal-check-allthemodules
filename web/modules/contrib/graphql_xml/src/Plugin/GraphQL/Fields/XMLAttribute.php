<?php

namespace Drupal\graphql_xml\Plugin\GraphQL\Fields;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Get an xml elements attribute value.
 *
 * @GraphQLField(
 *   id = "xml_attribute",
 *   secure = true,
 *   type = "String",
 *   name = "attribute",
 *   arguments = { "name": "String" },
 *   parents = { "XMLElement" }
 * )
 */
class XMLAttribute extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    if ($value instanceof \DOMElement) {
      yield $value->getAttribute($args['name']);
    }
  }

}
