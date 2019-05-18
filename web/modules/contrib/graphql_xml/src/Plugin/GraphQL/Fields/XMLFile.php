<?php

namespace Drupal\graphql_xml\Plugin\GraphQL\Fields;

use Drupal\file\FileInterface;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Expose xml file contents.
 *
 * @GraphQLField(
 *   id = "xml_file",
 *   name = "xml",
 *   secure = true,
 *   type = "XMLElement",
 *   parents = {"File"},
 * )
 */
class XMLFile extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    if ($value instanceof FileInterface) {
      if ($content = file_get_contents($value->getFileUri())) {
        $doc = new \DOMDocument();
        $doc->loadXML($content);
        yield $doc->documentElement;
      }
    }
  }

}
