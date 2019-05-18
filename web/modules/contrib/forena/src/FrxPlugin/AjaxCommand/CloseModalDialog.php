<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/17/16
 * Time: 4:40 PM
 */

namespace Drupal\forena\FrxPlugin\AjaxCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Class CloseDialog
 *
 * @FrxAjaxCommand(
 *   id = "closeModalDialog"
 * )
 */
class CloseModalDialog extends AjaxCommandBase {

  /**
   * Close a dialoge. 
   * @param array $settings
   *   Settings for command
   * @return CloseModalDialogCommand
   *   Close modal dialog command object. 
   */
  public function commandFromSettings(array $settings) {
    $selector = $this->getSetting($settings, 'selector');
    $persist = $this->getSetting($settings, 'persist') !== TRUE;
    return new CloseModalDialogCommand($selector, $persist);
  }

}