<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/17/16
 * Time: 4:25 PM
 */

namespace Drupal\forena\FrxPlugin\AjaxCommand;

use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Replace command
 * 
 * @FrxAjaxCommand(
 *   id = "replace"
 * )
 */
class Replace extends AjaxCommandBase {

  /**
   * JQuery Replace Command
   * 
   * Settings: 
   *   - selector: JQuery selector to use for Replace. 
   *   - text: HTML to use to replace
   * 
   * @param array $settings
   *   Settings for command
   * @return \Drupal\Core\Ajax\ReplaceCommand
   */
  public function commandFromSettings(array $settings) {
    $text = $this->getSetting($settings, 'text'); 
    $selector = $this->getSetting($settings, 'selector'); 
    return new ReplaceCommand($selector, $text, $settings); 
  }
}