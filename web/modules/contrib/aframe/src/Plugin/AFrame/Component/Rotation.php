<?php

namespace Drupal\aframe\Plugin\AFrame\Component;

use Drupal\aframe\AFrameComponentPluginBase;
use Drupal\aframe\AFrameComponentPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the "rotation" plugin.
 *
 * @AFrameComponent(
 *   id = "rotation",
 *   label = @Translation("Rotation"),
 * )
 */
class Rotation extends AFrameComponentPluginBase implements AFrameComponentPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'rotation' => [
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
      '#title'         => t('Rotation'),
      '#default_value' => $this->getSetting('rotation'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return t('Rotation: @x @y @z', [
      '@x' => $this->getSetting('rotation')['x'],
      '@y' => $this->getSetting('rotation')['y'],
      '@z' => $this->getSetting('rotation')['z'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $rotation = "{$this->getSetting('rotation')['x']} {$this->getSetting('rotation')['y']} {$this->getSetting('rotation')['z']}";
    if ($rotation != '0 0 0' && !empty($rotation)) {
      return $rotation;
    }
    return FALSE;
  }

}
