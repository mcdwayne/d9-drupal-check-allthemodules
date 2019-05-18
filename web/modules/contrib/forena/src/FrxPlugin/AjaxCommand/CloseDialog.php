<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 4/17/16
 * Time: 4:40 PM
 */

namespace Drupal\forena\FrxPlugin\AjaxCommand;
use Drupal\Core\Ajax\CloseDialogCommand;

/**
 * Class CloseDialog
 *
 * @FrxAjaxCommand(
 *   id = "closeDialog"
 * )
 */
class CloseDialog extends AjaxCommandBase {

  /**
   * Close a dialoge. 
   * @param array $settings
   *   Settings for command
   * @return CloseDialogCommand 
   *   Close dialog ajax command object. 
   */
  public function commandFromSettings(array $settings) {
    $selector = $this->getSetting($settings, 'selector');
    $persist = $this->getSetting($settings, 'persist') !== TRUE;
    return new CloseDialogCommand($selector, $persist);
  }

}