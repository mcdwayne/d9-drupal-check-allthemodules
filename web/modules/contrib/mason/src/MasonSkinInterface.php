<?php

namespace Drupal\mason;

/**
 * Provides an interface defining Mason skins.
 *
 * The hook_hook_info() is deprecated, and no resolution by 1/16/16:
 *   #2233261: Deprecate hook_hook_info()
 *     Postponed till D9
 */
interface MasonSkinInterface {

  /**
   * Returns the Mason skins.
   *
   * This can be used to register skins for the Mason. Skins will be
   * available when configuring the Optionset, Field formatter, or Views style,
   * or custom coded masons.
   *
   * Mason skins get a unique CSS class to use for styling, e.g.:
   * If your skin name is "my_module_mason_doe", the CSS class is:
   * mason--skin--my-module-mason-doe
   *
   * A skin can specify some CSS and JS files to include when Mason is
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
