<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/17/16
 * Time: 4:50 PM
 */

namespace Drupal\forena\FrxPlugin\AjaxCommand;

use Drupal\Core\Ajax\AddCssCommand;
use Drupal\Core\Ajax\CssCommand;

/**
 * Class Css
 * 
 * @FrxAjaxCommand(
 *   id = "css"
 * )
 */
class Css extends AjaxCommandBase {
  /**
   * CSS JQuery Command
   * @param array $settings
   * @return \Drupal\Core\Ajax\CssCommand
   */
  public function commandFromSettings(array $settings) {
    $selector = $this->getSetting($settings, 'selector');
    $css = $this->getJSONText($settings, 'css');
    return new CssCommand($selector, $css);
  }
}