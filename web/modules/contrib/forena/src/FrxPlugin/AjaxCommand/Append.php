<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/17/16
 * Time: 4:25 PM
 */

namespace Drupal\forena\FrxPlugin\AjaxCommand;

use Drupal\Core\Ajax\AppendCommand;

/**
 * Append command
 * 
 * @FrxAjaxCommand(
 *   id = "append"
 * )
 */
class Append extends AjaxCommandBase {

  /**
   * JQuery Append Command
   * 
   * Settings: 
   *   - selector: JQuery selector to use for append. 
   *   - text: HTML to use to replace
   * 
   * @param array $settings
   *   Settings for command
   * @return \Drupal\Core\Ajax\AppendCommand
   */
  public function commandFromSettings(array $settings) {
    $text = $this->getSetting($settings, 'text'); 
    $selector = $this->getSetting($settings, 'selector'); 
    return new AppendCommand($selector, $text, $settings); 
  }
}