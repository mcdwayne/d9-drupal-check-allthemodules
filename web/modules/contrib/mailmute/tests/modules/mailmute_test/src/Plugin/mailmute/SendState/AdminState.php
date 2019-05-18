<?php
/**
 * @file
 * Contains \Drupal\mailmute_test\Plugin\mailmute\SendState\AdminState.
 */

namespace Drupal\mailmute_test\Plugin\mailmute\SendState;

use Drupal\mailmute\Plugin\mailmute\SendState\SendStateBase;

/**
 * A send state that requires admin permission to be set.
 *
 * @SendState(
 *   id = "admin_state",
 *   label = "Admin state",
 *   description = "Used for testing.",
 *   mute = false,
 *   admin = true,
 *   parent_id = "send"
 * )
 */
class AdminState extends SendStateBase {
}
