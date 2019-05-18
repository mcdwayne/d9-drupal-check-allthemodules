<?php

namespace Drupal\inmail_mailmute\Plugin\mailmute\SendState;

/**
 * Indicates that the address owner is temporarily unreachable.
 *
 * @ingroup mailmute
 *
 * @SendState(
 *   id = "inmail_temporarily_unreachable",
 *   label = @Translation("Temporarily unreachable"),
 *   description = @Translation("The number of soft bounces has reached the configured threshold."),
 *   mute = true,
 *   admin = true,
 *   parent_id = "onhold"
 * )
 */
class TemporarilyUnreachable extends BounceSendstateBase {
}
