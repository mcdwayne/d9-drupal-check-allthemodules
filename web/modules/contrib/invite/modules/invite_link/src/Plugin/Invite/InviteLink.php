<?php

namespace Drupal\invite_link\Plugin\Invite;

use Drupal\invite\InvitePluginInterface;
use Drupal\Component\Plugin\PluginBase;

/**
 * Example plugin. Simplest use case.
 *
 * @Plugin(
 *   id="invite_link",
 *   label = @Translation("Invite Link")
 * )
 */
class InviteLink extends PluginBase implements InvitePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function send($invite) {
    // Intentionally empty. This plugin only generates a link.
  }

}
