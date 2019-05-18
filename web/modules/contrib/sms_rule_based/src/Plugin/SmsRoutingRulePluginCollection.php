<?php

namespace Drupal\sms_rule_based\Plugin;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * Provides a container for lazily loading SMS routing rules plugins.
 */
class SmsRoutingRulePluginCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   */
  protected $pluginKey = 'type';

}
