<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/17/16
 * Time: 4:25 PM
 */

namespace Drupal\forena\FrxPlugin\AjaxCommand;

use Drupal\Core\Ajax\PrependCommand;

/**
 * Prepend command
 * 
 * @FrxAjaxCommand(
 *   id = "prepend"
 * )
 */
class Prepend extends AjaxCommandBase {

  /**
   * JQuery Prepend Command
   * 
   * Settings: 
   *   - selector: JQuery selector to use for Prepend. 
   *   - text: HTML to use to replace
   * 
   * @param array $settings
   *   Settings for command
   * @return \Drupal\Core\Ajax\PrependCommand
   */
  public function commandFromSettings(array $settings) {
    $text = $this->getSetting($settings, 'text'); 
    $selector = $this->getSetting($settings, 'selector'); 
    return new PrependCommand($selector, $text, $settings); 
  }
}