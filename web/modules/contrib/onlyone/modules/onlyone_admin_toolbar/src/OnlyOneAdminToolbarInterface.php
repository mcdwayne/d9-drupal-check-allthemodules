<?php

namespace Drupal\onlyone_admin_toolbar;

/**
 * Interface OnlyOneAdminToolbarInterface.
 */
interface OnlyOneAdminToolbarInterface {

  /**
   * Rebuild the menu to change the menu label in the Admin Toolbar module.
   *
   * The menu will be rebuilded if the content type is configured to have only
   * one node.
   *
   * @param string $content_type
   *   The content type machine name.
   */
  public function rebuildMenu($content_type);

}
