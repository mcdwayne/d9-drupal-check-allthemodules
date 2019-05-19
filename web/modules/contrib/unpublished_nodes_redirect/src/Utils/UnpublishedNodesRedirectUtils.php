<?php

namespace Drupal\unpublished_nodes_redirect\Utils;

/**
 * Utility class for Unpublished nodes redirect module.
 */
class UnpublishedNodesRedirectUtils {

  /**
   * Helper function to get node types on the site and allow them to be altered.
   *
   * @return array
   *   An array of node types.
   */
  public function getNodeTypes() {
    // Get all the node types on the site.
    $node_types = \Drupal::entityTypeManager()->getStorage('node_type')
      ->loadMultiple();
    $node_types_array = array_keys($node_types);

    // Allow other modules to override this.
    \Drupal::moduleHandler()->alter('unpublished_nodes_redirect_node_types', $node_types_array);

    return $node_types_array;
  }

  /**
   * Gets the node type key used in this module.
   *
   * @param string $node_type
   *   Machine name of content type.
   *
   * @return string
   */
  public function getNodeTypeKey($node_type) {
    return $node_type . '_unpublished_redirect_path';
  }

  /**
   * Gets the response code key used in this module.
   *
   * @param string $node_type
   *   Machine name of content type.
   *
   * @return string
   */
  public function getResponseCodeKey($node_type) {
    return $node_type . '_unpublished_redirect_response_code';
  }

  /**
   * Checks that a node meets the criteria for a redirect.
   *
   * @param int $node_status
   *   Status of node 0 is unpulished and 1 is published.
   * @param bool $is_anonymous
   *   A boolean indicating if a user is anonymous.
   * @param string $redirect_path
   *   Path to be used for redirect.
   * @param string $response_code
   *   HTTP response code e.g 301.
   *
   * @return bool
   */
  public function checksBeforeRedirect($node_status, $is_anonymous, $redirect_path, $response_code) {
    // Node is unpublished, user is not logged in and there is a redirect path
    // and response code.
    if ($node_status == 0 && $is_anonymous && !empty($redirect_path)
        && !empty($response_code) && $response_code != 0) {
      return TRUE;
    }
    return FALSE;
  }

}
