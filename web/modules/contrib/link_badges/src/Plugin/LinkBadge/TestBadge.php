<?php

namespace Drupal\link_badges\Plugin\LinkBadge;

use Drupal\link_badges\LinkBadgeBase;

/**
 * A badge that displays "Test"
 *
 * @LinkBadge(
 *   id = "link_badge_test",
 *   label = "Test Badge"
 * )
 */
class TestBadge extends LinkBadgeBase {

  public function getBadgeValue() {
    return 'Test';
  }

}
