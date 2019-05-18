<?php

/**
 * @file
 * Contains \Drupal\hooks\HookInterface
 */

namespace Drupal\hooks;

/**
 * This interface must be implemented by all object orientated hooks.
 *
 * Your hook should be placed within $module/src/Hooks/$HookName so, if your
 * module name was 'custom_events' and the hook you wanted to implement was
 * hook_page_build_alter() which is fired with
 * \Drupal::moduleHandler()->alter('page_build', $page); then your OO hook would
 * live in custom_events/src/Hooks/PageBuild.php
 */
interface HookInterface {

  /**
   * @see \Drupal\Core\Extension\ModuleHandlerInterface::alter().
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL);

}
