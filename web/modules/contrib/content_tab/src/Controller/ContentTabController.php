<?php

/**
 * @file
 * Contains \Drupal\content_tab\Controller\ContentTabController.
 */

namespace Drupal\content_tab\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use \Drupal\node\Entity\Node;
use Drupal\node\NodeTypeInterface;
use Drupal\node\NodeInterface;


/**
 * Content tab controller.
 */
class ContentTabController extends ControllerBase {

  /**
   * Generate the content of the page.
   */
  public function getContent(UserInterface $user = NULL, NodeTypeInterface $node_type = NULL) {
    module_load_include('pages.inc', 'content_tab');
    return content_tab_page($user, $node_type);
  }

  /**
   * Generate the title of the page.
   */
  public function getTitle(UserInterface $user) {
    return "Test";
  }
}
