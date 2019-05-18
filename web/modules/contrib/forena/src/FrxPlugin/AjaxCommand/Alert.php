<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/17/16
 * Time: 4:19 PM
 */

namespace Drupal\forena\FrxPlugin\AjaxCommand;
use Drupal\Core\Ajax\AlertCommand;

/**
 * Alert
 * 
 * @FrxAjaxCommand(
 *   id = "alert"
 * )
 */
class Alert extends AjaxCommandBase {
  
  /**
   * 
   * @param array $settings
   * @return AlertCommand
   *   Ajax Alert Command object. 
   * 
   * Settings: 
   *   - text: The text message to use in the alert. 
   */
  public function commandFromSettings(array $settings) {
    $text = $this->getSetting($settings, 'text'); 
    return new AlertCommand($text); 
  }
}