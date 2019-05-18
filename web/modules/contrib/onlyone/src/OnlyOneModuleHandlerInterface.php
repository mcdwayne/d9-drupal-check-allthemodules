<?php

namespace Drupal\onlyone;

/**
 * Interface OnlyOneModuleHandlerInterface.
 */
interface OnlyOneModuleHandlerInterface {

  /**
   * Returns a link to the module help page.
   *
   * @param string $module_machine_name
   *   The module machine name.
   * @param string $module_name_alternate
   *   Alternate module name to use if the module is not present in the site.
   * @param string $emphasize
   *   Use this parameter to wrap with <em> tags the module name if the module
   *   is not installed or not present in the site.
   *
   * @return string
   *   Returns a link to the module help page if the module is installed, the
   *   alternate module name otherwise.
   */
  public function getModuleHelpPageLink($module_machine_name, $module_name_alternate, $emphasize = FALSE);

}
