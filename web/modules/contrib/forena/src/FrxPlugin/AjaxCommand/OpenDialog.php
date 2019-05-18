<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/17/16
 * Time: 5:41 PM
 */

namespace Drupal\forena\FrxPlugin\AjaxCommand;


use Drupal\Core\Ajax\OpenDialogCommand;

/**
 * Class OpenDialog
 * 
 * @FrxAjaxCommand(
 *   id = "openDialog"
 * )
 */
class OpenDialog extends AjaxCommandBase {
  public function commandFromSettings(array $settings) {
    $selector = $this->getSetting($settings, 'selector');
    $title = $this->getSetting($settings, 'title');
    $content = $this->getSetting($settings, 'text');
    return new OpenDialogCommand($selector, $title, $content, $settings);
  }
}