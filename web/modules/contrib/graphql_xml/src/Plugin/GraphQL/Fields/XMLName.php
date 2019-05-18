<?php

namespace Drupal\graphql_xml\Plugin\GraphQL\Fields;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Get an xml elements tag name.
 *
 * @GraphQLField(
 *   id = "xml_name",
 *   secure = true,
 *   type = "String",
 *   name = "name",
 *   parents = { "XMLElement" }
 * )
 */
class XMLName extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    if ($value instanceof \DOMElement) {
      yield $value->tagName;
    }
  }

}
