<?php

namespace Drupal\toggle_sticky_toggler\Plugin\Menu\LocalTask;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a local task plugin with a dynamic title.
 */
class ToggleStickyTogglerLocalTask extends LocalTaskDefault {

  use StringTranslationTrait;

  /**
   * A node object.
   *
   * @var \Drupal\node\Entity\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {

    $this->node = Node::load($route_match->getRawParameter('node'));

    return parent::getRouteParameters($route_match);
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {

    return $this->node->isSticky() ? $this->t('Unmake Sticky') : $this->t('Make Sticky');
  }

}
