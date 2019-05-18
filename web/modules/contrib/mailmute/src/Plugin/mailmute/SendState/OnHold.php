<?php
/**
 * @file
 * Contains \Drupal\mailmute\Plugin\mailmute\SendState\OnHold.
 */

namespace Drupal\mailmute\Plugin\mailmute\SendState;

/**
 * Indicates that the address owner requested muting until further notice.
 *
 * @ingroup plugin
 *
 * @SendState(
 *   id = "onhold",
 *   label = @Translation("On hold"),
 *   description = @Translation("The address owner requested muting until further notice."),
 *   mute = true,
 *   admin = false
 * )
 */
class OnHold extends SendStateBase {
}
