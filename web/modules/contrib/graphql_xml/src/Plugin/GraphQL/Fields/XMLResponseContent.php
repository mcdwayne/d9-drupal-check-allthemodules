<?php

namespace Drupal\graphql_xml\Plugin\GraphQL\Fields;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql_core\Plugin\GraphQL\Fields\Routing\Response\ResponseContent;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Get the response content of an internal or external request as xml document.
 *
 * @GraphQLField(
 *   id = "xml_response_content",
 *   secure = true,
 *   name = "xml",
 *   type = "XMLElement",
 *   parents = {"InternalResponse", "ExternalResponse"}
 * )
 */
class XMLResponseContent extends ResponseContent {

  /**
   * {@inheritdoc}
   */
  protected function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    foreach (parent::resolveValues($value, $args, $context, $info) as $item) {
      $document = new \DOMDocument();
      libxml_use_internal_errors(TRUE);
      $document->loadHTML($item);
      yield $document->documentElement;
    }
  }

}
