<?php

namespace Drupal\tmgmt_smartling_log_settings\Logger;

use Drupal\Core\Logger\LoggerChannelFactory as LoggerChannelFactoryCore;

/**
 * Defines a factory for logging channels.
 */
class LoggerChannelFactory extends LoggerChannelFactoryCore {

  /**
   * {@inheritdoc}
   */
  public function get($channel) {
    if (!isset($this->channels[$channel])) {
      // Same as core's LoggerChannelFactory but we instantiate
      // Drupal\tmgmt_smartling_log_settings\Logger\LoggerChannel instead
      // of Drupal\Core\Logger\LoggerChannelFactory.
      $instance = new LoggerChannel($channel);

      // If we have a container set the request_stack and current_user services
      // on the channel. It is up to the channel to determine if there is a
      // current request.
      if ($this->container) {
        $instance->setRequestStack($this->container->get('request_stack'));
        $instance->setCurrentUser($this->container->get('current_user'));
      }

      // Pass the loggers to the channel.
      $instance->setLoggers($this->loggers);
      $this->channels[$channel] = $instance;
    }

    return $this->channels[$channel];
  }

}
