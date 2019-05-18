<?php

namespace Drupal\module_blacklist;

use Drupal\Core\Extension\ModuleInstaller;

/**
 * Class ModuleBlacklistModuleInstaller.
 *
 * @package Drupal\module_blacklist
 */
class ModuleBlacklistModuleInstaller extends ModuleInstaller {

  /**
   * Performs module pre-install rollback operations.
   *
   * This method performs rollback operations for module installation process
   * executed before the invocation of the hook hook_module_preinstall().
   *
   * @param string $module
   *   The module name.
   */
  public function rollbackPreinstall($module) {
    // Remove the module's entry from the config. Don't check schema when
    // uninstalling a module since we are only clearing a key.
    \Drupal::configFactory()->getEditable('core.extension')->clear("module.$module")->save(TRUE);

    // Update the module handler to remove the pre-installed module.
    $module_filenames = $this->moduleHandler->getModuleList();
    unset($module_filenames[$module]);
    $this->moduleHandler->setModuleList($module_filenames);

    // Clear the static cache of system_rebuild_module_data() to pick up the
    // new module, since it merges the installation status of modules into
    // its statically cached list.
    drupal_static_reset('system_rebuild_module_data');

    // Update the kernel to exclude the uninstalled modules.
    $this->updateKernel($module_filenames);
  }

}
