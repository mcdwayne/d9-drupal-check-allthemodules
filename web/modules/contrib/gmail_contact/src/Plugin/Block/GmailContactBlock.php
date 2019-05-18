<?php

/**
 * @file
 * Contains \Drupal\gmail_contact\Plugin\Block.
 */

namespace Drupal\gmail_contact\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'gmail contact invite' block.
 *
 * @Block(
 *   id = "gmail_contact_block",
 *   admin_label = @Translation("Gmail Contact Invite"),
 * )
 */
class GmailContactBlock extends BlockBase {
  public function build() {

    $gmail_url = gmail_contact_get_invite_link_url();
    $gmail_options = array(
      'attributes' => array(
        'target' => "_blank",
        'rel' => "nofollow",
      ),
    );

    $url = Url::fromUri($gmail_url);
    $content = '<div class="gmail-contact-invite-link">' . \Drupal::l(t('Invite Gmail Contacts'), $url) . '</div>';

    return array(
      '#type' => 'markup',
      '#markup' => $content,
    );
  }

}
?>
