<?php

namespace Drupal\scheduled_message;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * Collection of ScheduledMessagePlugins for entity type config page.
 */
class ScheduledMessagePluginCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

}
