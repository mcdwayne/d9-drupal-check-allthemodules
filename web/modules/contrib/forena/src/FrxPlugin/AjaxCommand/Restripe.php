<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/17/16
 * Time: 5:58 PM
 */

namespace Drupal\forena\FrxPlugin\AjaxCommand;


use Drupal\Core\Ajax\RestripeCommand;

/**
 * Class Restripe
 * 
 * @FrxAjaxCommand(
 *   id = "restripe"
 * )
 */
class Restripe extends AjaxCommandBase {
  public function commandFromSettings(array $settings) {
    $selector = $this->getSetting($settings, 'selector');
    return new RestripeCommand($selector);
  }
}