<?php
/**
 * @file
 * Contains \Drupal\edit_content_type_tab\Controller\EditController
 */

namespace Drupal\edit_content_type_tab\Controller;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Controller routines for edit_content_type_tab routes.
 */
class EditController extends ControllerBase  {

  /**
   * Returns a page to edit the content type of the current node.
   *
   * @return object
   *   An HTTP response to the content type editing page
   */
  public function editLink($node) {

    // Load in the node and determine the content type it belongs to
    $loadedNode = entity_load('node', $node);
    $nodeType = $loadedNode->gettype();

    // Create a URI to the content type edit page
    $uri = 'base://admin/structure/types/manage/' . $nodeType;
    $url = Url::fromUri($uri);

    // Add in the destination parameter, so we can return to this node
    // after editing.
    $url->setOptions(
      array('query' => array('destination' => 'node/' . $node))
    );

    // Create the redirect and return it.
    $redirect = new RedirectResponse($url->toString());
    return $redirect;
  }
}
