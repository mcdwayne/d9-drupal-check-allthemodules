<?php

namespace Drupal\inmail_mailmute\Plugin\mailmute\SendState;

/**
 * Indicates that hard bounces have been received from the address.
 *
 * @ingroup mailmute
 *
 * @SendState(
 *   id = "inmail_invalid_address",
 *   label = @Translation("Invalid address"),
 *   description = @Translation("Earlier messages to the address have resulted in hard bounces."),
 *   mute = true,
 *   admin = true,
 *   parent_id = "onhold"
 * )
 */
class InvalidAddress extends BounceSendstateBase {
}
