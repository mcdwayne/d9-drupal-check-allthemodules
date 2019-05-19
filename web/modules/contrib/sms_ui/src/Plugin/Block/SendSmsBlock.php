<?php

namespace Drupal\sms_ui\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\sms_ui\Form\BulkComposeForm;

/**
 * Provides a block that contains the SMS sending form.
 *
 * @Block(
 *   id = "sms_ui_send_block",
 *   admin_label = @Translation("Send SMS")
 * )
 */
class SendSmsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'send sms');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm(BulkComposeForm::class);
  }

}
