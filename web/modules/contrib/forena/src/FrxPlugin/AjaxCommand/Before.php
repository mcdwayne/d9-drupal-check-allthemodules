<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/17/16
 * Time: 4:25 PM
 */

namespace Drupal\forena\FrxPlugin\AjaxCommand;

use Drupal\Core\Ajax\BeforeCommand;

/**
 * Before command
 * 
 * @FrxAjaxCommand(
 *   id = "before"
 * )
 */
class Before extends AjaxCommandBase {

  /**
   * JQuery Before Command
   * 
   * Settings: 
   *   - selector: JQuery selector to use for Before. 
   *   - text: HTML to use to replace
   * 
   * @param array $settings
   *   Settings for command
   * @return \Drupal\Core\Ajax\BeforeCommand
   */
  public function commandFromSettings(array $settings) {
    $text = $this->getSetting($settings, 'text'); 
    $selector = $this->getSetting($settings, 'selector'); 
    return new BeforeCommand($selector, $text, $settings); 
  }
}