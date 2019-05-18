<?php

namespace Drupal\micon_menu;

use Drupal\Core\Menu\MenuLinkDefault;

/**
 * Manages discovery, instantiation, and tree building of menu link plugins.
 *
 * This manager finds plugins that are rendered as menu links.
 */
class MiconMenuLinkDefault extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  protected $overrideAllowed = [
    'menu_name' => 1,
    'parent' => 1,
    'weight' => 1,
    'expanded' => 1,
    'enabled' => 1,
    // Allow override of this variable.
    'options' => 1,
  ];

}
