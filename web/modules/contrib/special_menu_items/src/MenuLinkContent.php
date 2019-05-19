<?php

/**
 * @file
 * Contains \Drupal\menu_link_content\Entity\MenuLinkContent.
 */

namespace Drupal\special_menu_items;

use Drupal\menu_link_content\Entity\MenuLinkContent as OriginalMenuLinkContent;

/**
 * Overrides the MenuLinkContent class.
 */
class MenuLinkContent extends OriginalMenuLinkContent {
    /**
     * {@inheritdoc}
     */
    public function getUrlObject() {
        if (isset($this->link->first()->uri)) {
            return $this->link->first()->getUrl();
        }
    }
}
