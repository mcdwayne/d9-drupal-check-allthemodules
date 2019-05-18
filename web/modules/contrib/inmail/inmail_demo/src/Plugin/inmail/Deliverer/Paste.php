<?php

namespace Drupal\inmail_demo\Plugin\inmail\Deliverer;

use Drupal\inmail\Plugin\inmail\Deliverer\DelivererBase;

/**
 * Deliverer for emails manually entered in the UI.
 *
 * @ingroup deliverer
 *
 * @Deliverer(
 *   id = "paste",
 *   label = @Translation("Paste")
 * )
 */
class Paste extends DelivererBase {
}
