<?php

namespace Drupal\visualn\Core;
use Drupal\visualn\Core\DrawerInterface;
use Drupal\visualn\Core\VisualNPluginJsInterface;

/**
 * Interface for VisualN Drawer plugins using js.
 *
 * @see \Drupal\visualn\Core\DrawerWithJsBase
 *
 * @ingroup drawer_plugins
 */
interface DrawerWithJsInterface extends DrawerInterface, VisualNPluginJsInterface {

}
