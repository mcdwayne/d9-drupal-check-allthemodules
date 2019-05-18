<?php

namespace Drupal\ad_entity\Plugin\ad_entity\AdContext;

use Drupal\ad_entity\Plugin\AdContextBase;

/**
 * Turnoff context plugin.
 *
 * @AdContext(
 *   id = "turnoff",
 *   label = @Translation("Turn off Advertisement"),
 *   library = "ad_entity/turnoff_context"
 * )
 */
class TurnoffContext extends AdContextBase {}
