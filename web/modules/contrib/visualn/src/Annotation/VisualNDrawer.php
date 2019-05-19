<?php

namespace Drupal\visualn\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VisualN Drawer item annotation object.
 *
 * @see \Drupal\visualn\Manager\DrawerManager
 * @see plugin_api
 *
 * @ingroup drawer_plugins
 *
 * @Annotation
 */
class VisualNDrawer extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The builder plugin id  of the plugin.
   *
   * @var string
   */
  public $builder = 'visualn_default';

  /**
   * The data input type of the plugin.
   *
   * @var string
   */
  public $input = 'generic_js_data_array';

  /**
   * The data output type of the plugin. Generally, not used.
   *
   * @var string
   */
  public $output = '';
  // @todo: should drawers return any resource by default?
  //public $output = 'generic_js_data_array';

  /**
   * The role type of the plugin.
   *
   * "drawer" is a common case and used for most plugins (base drawers)
   * "wrapper" is used for plugins that serve as wrappers for base drawers in case of sudrawers
   *    wrappers are never used as base drawers and never shown in base drawers
   *    select lists (e.g. in VisualN styles UI)
   *
   * @var string
   */
  public $role = 'drawer';

  /**
   * The default wrapper to use for the drawer when used in subdrawers.
   *
   * @var string
   */
  public $wrapper_drawer_id = 'visualn_default_drawer_wrapper';

  // @todo: add "wrappable" key or use "wrapper_drawer_id" to show that drawer can't be wrapped
  //    e.g. used as a base for subdrawer (e.g. set "wrapper_drawer_id" to blank value
  //    in the drawer class annotation). most drawers should be wrappable

}
