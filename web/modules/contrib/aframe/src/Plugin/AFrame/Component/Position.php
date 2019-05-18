<?php

namespace Drupal\aframe\Plugin\AFrame\Component;

use Drupal\aframe\AFrameComponentPluginBase;
use Drupal\aframe\AFrameComponentPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the "position" plugin.
 *
 * @AFrameComponent(
 *   id = "position",
 *   label = @Translation("Position"),
 * )
 */
class Position extends AFrameComponentPluginBase implements AFrameComponentPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'position' => [
        'x' => 0,
        'y' => 0,
        'z' => 0,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [
      '#type'          => 'aframe_coords',
      '#title'         => t('Position'),
      '#default_value' => $this->getSetting('position'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return t('Position: @x @y @z', [
      '@x' => $this->getSetting('position')['x'],
      '@y' => $this->getSetting('position')['y'],
      '@z' => $this->getSetting('position')['z'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $positiion = "{$this->getSetting('position')['x']} {$this->getSetting('position')['y']} {$this->getSetting('position')['z']}";
    if ($positiion != '0 0 0' && !empty($positiion)) {
      return $positiion;
    }
    return FALSE;
  }

}
