<?php

namespace Drupal\aframe\Plugin\AFrame\Component;

use Drupal\aframe\AFrameComponentPluginBase;
use Drupal\aframe\AFrameComponentPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the "look-at" plugin.
 *
 * @AFrameComponent(
 *   id = "look-at",
 *   label = @Translation("Look-At"),
 * )
 */
class LookAt extends AFrameComponentPluginBase implements AFrameComponentPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'look-at' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [
      '#type'          => 'textfield',
      '#title'         => t('Look-At'),
      '#default_value' => $this->getSetting('look-at'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return t('Look-At: @look-at', ['@look-at' => $this->getSetting('look-at')]);
  }

}
