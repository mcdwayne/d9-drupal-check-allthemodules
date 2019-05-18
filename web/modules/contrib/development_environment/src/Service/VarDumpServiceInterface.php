<?php

namespace Drupal\development_environment\Service;

/**
 * Interface for varDumpService.
 */
interface VarDumpServiceInterface {

  /**
   * Dumps the details of a variable.
   *
   * @param mixed $var
   *   The variable for which details should be dumped.
   * @param bool $return
   *   Whether or not to return the data to the calling function. If FALSE, the
   *   data is dumped to the screen.
   * @param bool $html
   *   Whether or not the outputted data should be an HTML output, or plaintext.
   * @param int $level
   *   The depth of $var that should be dumped. Set to 0 (zero) for full depth.
   *
   * @return array|null
   *   An array containing the dumped data if $return is set to TRUE, or NULL
   *   otherwise.
   */
  public function varDump($var, $return = FALSE, $html = FALSE, $level = 0);

}
