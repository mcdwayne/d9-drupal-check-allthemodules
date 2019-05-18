<?php

namespace Drupal\flashpoint_lrs_client\Plugin\flashpoint_settings;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\flashpoint\FlashpointSettingsInterface;


/**
 * @FlashpointSettings(
 *   id = "lrs_client",
 *   label = @Translation("LRS Client"),
 * )
 */
class LRSClient extends PluginBase implements FlashpointSettingsInterface {
  /**
   * @return string
   *   A string description.
   */
  public function description()
  {
    return $this->t('Settings for Video.js content');
  }

  /**
   * Provide form options for the settings form.
   * @return array
   *   Array of Form API form elements.
   */
  public static function getFormOptions() {
    $flashpoint_config = \Drupal::configFactory()->getEditable('flashpoint.settings');

    // Set the LRS Client Options
    $client_options = [];
    $plugin_manager = \Drupal::service('plugin.manager.flashpoint_lrs_client');
    $plugin_definitions = $plugin_manager->getDefinitions();
    foreach ($plugin_definitions as $pd) {
      $client_options[$pd['id']] = $pd['label'];
    }

    $form_options = [
      'flashpoint_lrs' => [
        '#type' => 'details',
        '#title' => 'Flashpoint LRS',
        'lrs_client' => [
          '#type' => 'select',
          '#title' => t('LRS Client'),
          '#description' => t('Choose the LRS client for determining pass status.'),
          '#empty_option' => t(' - Select - '),
          '#default_value' => $flashpoint_config->getOriginal('lrs_client'),
          '#options' => $client_options,
        ],
        'lrs_connector' => [
          '#type' => 'textfield',
          '#title' => t('Spectra Connector for LRS Client'),
          '#description' => t('Machine name of the Spectra Connect entity to be used by the LRS Client.'),
          '#default_value' => $flashpoint_config->getOriginal('lrs_connector'),
        ],
      ],
    ];

    return $form_options;
  }

}
