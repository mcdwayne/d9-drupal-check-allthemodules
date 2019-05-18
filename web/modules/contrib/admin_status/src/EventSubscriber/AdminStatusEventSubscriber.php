<?php

namespace Drupal\admin_status\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\admin_status\AdminStatusPluginManager;

/**
 * Class AdminStatusEventSubscriber.
 *
 * This class catches kernel.request events and displays messages from enabled
 * plugins.
 *
 * @package Drupal\admin_status
 */
class AdminStatusEventSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\admin_status\AdminStatusPluginManager definition.
   *
   * @var \Drupal\admin_status\AdminStatusPluginManager
   */
  protected $adminStatusManager;

  /**
   * Construct an AdminStatusEventSubscriber object.
   *
   * @param \Drupal\admin_status\AdminStatusPluginManager $plugin_manager_admin_status
   *   The Admin Status Plugin Manager.
   */
  public function __construct(
      AdminStatusPluginManager $plugin_manager_admin_status) {
    $this->adminStatusManager = $plugin_manager_admin_status;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Tell Symfony that we want to hear about kernel.request events.
    $events['kernel.request'] = ['kernelRequest'];
    return $events;
  }

  /**
   * Handles kernel.request events.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The Symfony event.
   */
  public function kernelRequest(Event $event) {
    static $renderer;

    // Get our saved config data.
    $config = \Drupal::config('admin_status.settings');
    $plugin_status = $config->get('plugin_status');

    // Run through each plugin and invoke its message and status methods if it's
    // enabled.
    foreach ($plugin_status as $plugin_id => $status) {
      if ($status['enabled']) {
        // Create the message with the configuration values.
        $plugin = $this->adminStatusManager->createInstance(
          $plugin_id, ['of' => 'configuration values']
        );
        $configValues = empty($status['config']) ? [] : $status['config'];
        $messages = $plugin->message($configValues);
        if (isset($messages['status'])) {
          $messages = [$messages];
        }

        foreach ($messages as $message) {
          // If the plugin returns an empty message, there is nothing to
          // display. Otherwise, get the status and display the message.
          if (!empty($message)) {
            $msgText = $message['message'];
            if (is_array($msgText)) {
              if (empty($renderer)) {
                $renderer = \Drupal::service('renderer');
              }
              $msgText = $renderer->renderPlain($msgText);
            }
            drupal_set_message($msgText, $message['status']);
          }
        }
      }
    }
  }

}
