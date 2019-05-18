<?php

namespace Drupal\monster_menus\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\NullDestination;

// The core Drupal\migrate\Plugin\migrate\destination\NullDestination plugin
// contains what is probably a typo: it has requirements_met = false in its
// annotation. This effectively renders it useless.

/**
 * Provides null destination plugin.
 *
 * @MigrateDestination(
 *   id = "mm_null",
 * )
 */
class MMNullDestination extends NullDestination {
}
