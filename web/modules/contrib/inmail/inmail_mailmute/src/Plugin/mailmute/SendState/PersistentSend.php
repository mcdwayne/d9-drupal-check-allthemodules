<?php

namespace Drupal\inmail_mailmute\Plugin\mailmute\SendState;

/**
 * Indicates that messages should be sent, and no transitions allowed.
 *
 * This is useful to protect against bluff bounces which could otherwise be used
 * to mute innocent users. A better protection is always to enable VERP or
 * similar recipient identification, if possible.
 *
 * @ingroup mailmute
 *
 * @SendState(
 *   id = "persistent_send",
 *   label = @Translation("Persistent send"),
 *   description = @Translation("Messages are not suppressed, and automatic transition to other states is disabled."),
 *   mute = false,
 *   admin = true,
 *   parent_id = "send"
 * )
 */
class PersistentSend extends BounceSendstateBase {
}
