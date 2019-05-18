<?php

namespace Drupal\Tests\menu_link_weight\FunctionalJavascript;

use Drupal\node\NodeInterface;

trait MenuLinkWeightTestTrait {

  /**
   * Asserts the weight of a menu link.
   *
   * @param string $link_id
   *   The plugin ID of the link.
   * @param int $expected_weight
   *   The expected weight of the link.
   */
  protected function assertLinkWeight($link_id, $expected_weight) {
    $this->menuLinkManager->resetDefinitions();
    // Retrieve the menu link.
    /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
    $link = $this->menuLinkManager->createInstance($link_id);
    $this->assertEquals($expected_weight, $link->getWeight());
  }

  /**
   * Load menu link for a given node.
   *
   * @param \Drupal\node\NodeInterface $node
   * @return \Drupal\Core\Menu\MenuLinkInterface|FALSE
   */
  protected function loadMenuLinkByNode(NodeInterface $node) {
    $links = $this->menuLinkManager->loadLinksByRoute($node->toUrl()->getRouteName(), $node->toUrl()->getRouteParameters());
    return !empty($links) ? reset($links) : FALSE;
  }

}
