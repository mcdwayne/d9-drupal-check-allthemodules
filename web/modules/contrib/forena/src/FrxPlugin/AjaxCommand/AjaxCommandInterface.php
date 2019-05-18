<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/16/16
 * Time: 2:28 PM
 */

namespace Drupal\forena\FrxPlugin\AjaxCommand;

/**
 * Implements ajax commands.
 */
interface AjaxCommandInterface {
  /**
   * Settings are passed into this factory from either the skin or the report
   * element. If there are complex structures when used in the arguments they
   * will be passed in the 'text' setting.  These should be decoded by any
   * plugin intending to use the interior element of an AJAX command.
   *
   * @param array $settings
   * @return mixed
   *   The ajax command object expected by drupal.
   */
  public function commandFromSettings(array $settings);
}