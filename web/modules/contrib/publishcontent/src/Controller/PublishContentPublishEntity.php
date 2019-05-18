<?php

namespace Drupal\publishcontent\Controller;

use \Drupal\node\NodeInterface;
use \Symfony\Component\HttpFoundation\RedirectResponse;
use \Drupal\Core\Url;

class PublishContentPublishEntity {

  /**
   * Toggle node status.
   *
   * @param NodeInterface $node
   * @return RedirectResponse
   */
  public function toggleEntityStatus(NodeInterface $node) {
    $node->setPublished(!$node->isPublished());
    $node->save();

    $redirectUrl = Url::fromUri(\Drupal::request()->server->get('HTTP_REFERER'), ['absolute' => TRUE])->getUri();

    return new RedirectResponse($redirectUrl);
  }

}
