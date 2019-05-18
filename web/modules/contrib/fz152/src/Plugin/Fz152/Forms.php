<?php

namespace Drupal\fz152\Plugin\Fz152;

use Drupal\fz152\Form\Fz152SettingsForms;
use Drupal\fz152\Fz152PluginBase;

/**
 * Provides an annotated Fz152 plugin for config forms.
 *
 * @Fz152(
 *   id = "forms",
 * )
 */
class Forms extends Fz152PluginBase {

  /**
   * {@inheritdoc}
   */
  public function getSettingsPage() {
    return [
      'path' => 'forms',
      'title' => 'Forms',
      'form' => Fz152SettingsForms::class,
      'weight' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getForms() {
    $config = \Drupal::config('fz152.forms');
    $forms_settings = $config->get('forms');
    $forms = [];
    if (!empty($forms_settings)) {
      foreach (explode(PHP_EOL, $forms_settings) as $form_id) {
        $form_id_exploded = explode('|', $form_id);
        $forms[] = [
          'form_id' => $form_id_exploded[0],
          'weight' => isset($form_id_exploded[1]) ? $form_id_exploded[1] : NULL,
        ];
      }
    }
    return $forms;
  }

}
