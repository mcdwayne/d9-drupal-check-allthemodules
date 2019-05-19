<?php

namespace Drupal\symlink\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class SymlinkController.
 *
 * @package Drupal\symlink\Controller
 */
class SymlinkController extends ControllerBase {

  /**
   * Method for redirecting to the page for creating the symlink.
   *
   * @param int $nid
   *    The node ID to be symlink'ed.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *    Returns a redirect response for showing the symlink node add page.
   */
  public function addSymlink($nid) {

    return $this->redirect('node.add', ['node_type' => 'symlink'], [
      'absolute' => TRUE,
      'query' => array(
        'symlink' => $nid,
      ),
    ]);
  }

}
