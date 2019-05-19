<?php

namespace Drupal\slack_rtm\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Slack RTM Messages.
 */
class SlackRtmMessageViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
