<?php

namespace Drupal\api_tokens_example\Plugin\ApiToken;

use Drupal\node\Entity\Node;
use Drupal\api_tokens\ApiTokenBase;

/**
 * Provides a Node API token.
 *
 * Token examples:
 * - [api:node[123]/]
 * - [api:node[123, "teaser"]/]
 *
 * @ApiToken(
 *   id = "node",
 *   label = @Translation("Node"),
 *   description = @Translation("Renders a node.")
 * )
 */
class NodeApiToken extends ApiTokenBase {

  /**
   * {@inheritdoc}
   */
  public function validate(array $params) {
    // For [api:node[123]/] token:
    //$params = [
    //  'id' => 123,
    //  'view_mode' => 'full',
    //];

    // For [api:node[123, "teaser"]/] token:
    //$params = [
    //  'id' => 123,
    //  'view_mode' => 'teaser',
    //];

    // Check that "nid" is a valid node ID.
    if (!preg_match('@\d+@', $params['id'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Build callback.
   *
   * @param int $id
   *   The node ID.
   * @param string $view_mode
   *   (optional) The view mode to render a node in. Defaults to "full".
   *
   * return array
   *   A renderable array.
   *
   * @see \Drupal\api_tokens\ApiTokenPluginInterface::build();
   */
  public function build($id, $view_mode = 'full') {
    $build = [];
    $node = Node::load($id);
    if ($node && $node->access('view')) {
      $build = \Drupal::entityTypeManager()
        ->getViewBuilder('node')
        ->view($node, $view_mode);
    }

    return $build;
  }

}
