<?php

namespace Drupal\toggle_sticky_toggler\Controller;

use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Toggle Sticky Toggler controller.
 */
class ToggleStickyToggler {

  /**
   * Toggle a node's sticky status.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The requested node object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response back to URL where the link was clicked.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function toggleStickyStatus(NodeInterface $node) {

    $node->setSticky(!$node->isSticky());
    $node->save();

    $redirectUrl = Url::fromUri(\Drupal::request()->server
      ->get('HTTP_REFERER'), ['absolute' => TRUE])->getUri();

    return new RedirectResponse($redirectUrl);
  }

}
