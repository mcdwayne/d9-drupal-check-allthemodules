<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/17/16
 * Time: 5:55 PM
 */

namespace Drupal\forena\FrxPlugin\AjaxCommand;


use Drupal\Core\Ajax\RemoveCommand;

/**
 * Class Remove
 * 
 * @FrxAjaxCommand(
 *   id = "remove"
 * )
 */
class Remove extends AjaxCommandBase {
  public function commandFromSettings(array $settings) {
    $selector = $this->getSetting($settings, 'selector');
    return new RemoveCommand($selector);
  }
}