<?php
namespace Drupal\monster_menus\MMRenderer;

/**
 * @file
 * Interface used by the monster_menus.tree_renderer service.
 */

interface MMRendererInterface {

  public function create(array $tree, $start = 0);

  public function render();

  public function walk($is_top = TRUE);

  public function alterItem(\stdClass $leaf, array $item);

  public function leafIsVisible(\stdClass $leaf);

}
