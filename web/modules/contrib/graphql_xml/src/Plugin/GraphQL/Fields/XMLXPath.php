<?php

namespace Drupal\graphql_xml\Plugin\GraphQL\Fields;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Evaluate a XPath query on the current element.
 *
 * @GraphQLField(
 *   id = "xml_xpath",
 *   secure = true,
 *   type = "XMLElement",
 *   multi = true,
 *   arguments = {
 *     "query" = "String"
 *   },
 *   name = "xpath",
 *   parents = { "XMLElement" }
 * )
 */
class XMLXPath extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    if ($value instanceof \DOMElement) {
      $xpath = new \DOMXPath($value->ownerDocument);
      foreach ($xpath->query($args['query'], $value) as $item) {
        yield $item;
      }
    }
  }

}
