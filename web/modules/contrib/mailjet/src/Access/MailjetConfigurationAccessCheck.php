<?php

namespace Drupal\mailjet\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Checks access for displaying configuration Mailjet pages.
 */
class MailjetConfigurationAccessCheck implements AccessInterface {

  /**
   * Access check for Mailjet module configuration.
   *
   * Ensures a Mailjet API keys has been provided.
   *
   */
  public function access(AccountInterface $account) {
    global $base_url;
    $config_mailjet = \Drupal::config('mailjet.settings');

    $user = \Drupal::currentUser();

    // Check for permission

    if ($user->hasPermission('access administration pages') == TRUE) {

      if (!empty($config_mailjet->get('mailjet_active'))
        && !empty($config_mailjet->get('mailjet_username'))
        && !empty($config_mailjet->get('mailjet_password'))) {

        return AccessResult::allowed();
      }
      else {
        if ($_SERVER["REQUEST_URI"] !== '/admin/config/system/mailjet/api') {
          drupal_set_message(t('You need to add your MailJet API details before you can continue! Enter your Mailjet Api keys <a href="'.$base_url.'/admin/config/system/mailjet/api">here.</a>'), 'warning');
        }
        return AccessResult::forbidden();
      }
    }
    else {
      return AccessResult::forbidden();
    }
  }

}