<?php

namespace Drupal\xhprof\Extension;

/**
 * Interface ExtensionInterface
 */
interface ExtensionInterface {

  /**
   * Returns TRUE if this extension is loaded into the PHP interpreter.
   *
   * @return mixed
   */
  public static function isLoaded();

  /**
   * Returns the options supported by this extension.
   *
   * @return mixed
   */
  public function getOptions();

  /**
   * Enable the extension.
   *
   * @param $modifier
   * @param $options
   *
   * @return mixed
   */
  public function enable($modifier, $options);

  /**
   * Disable the extension.
   *
   * @return mixed
   */
  public function disable();

}
