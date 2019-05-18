<?php

namespace Drupal\gridstack;

/**
 * Provides an interface defining GridStack skins.
 *
 * The hook_hook_info() is deprecated, and no resolution by 1/16/16:
 *   #2233261: Deprecate hook_hook_info()
 *     Postponed till D9
 */
interface GridStackSkinInterface {

  /**
   * Returns the GridStack skins.
   *
   * This can be used to register skins for the GridStack. Skins will be
   * available when configuring the Optionset, Field formatter, or Views style,
   * or custom coded gridstacks.
   *
   * GridStack skins get a unique CSS class to use for styling, e.g.:
   * If your skin name is "my_module_gridstack_doe", the CSS class is:
   * gridstack--skin--my-module-gridstack-doe
   *
   * A skin can specify some CSS and JS files to include when GridStack is
   * displayed, except for a thumbnail skin which accepts CSS only.
   *
   * Each skin supports 5 keys:
   * - name: The human readable name of the skin.
   * - description: The description about the skin, for help and manage pages.
   * - css: An array of CSS files to attach.
   * - js: An array of JS files to attach, e.g.: image zoomer, reflection, etc.
   * - provider: A module name registering the skins.
   *
   * @return array
   *   The array of the main and thumbnail skins.
   */
  public function skins();

}
