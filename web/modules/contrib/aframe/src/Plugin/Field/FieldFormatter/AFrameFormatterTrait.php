<?php

namespace Drupal\aframe\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AFrameFormatterTrait.
 *
 * @package Drupal\aframe\Plugin\Field\FieldFormatter
 *
 * @todo Use a plugin manager to get global settings from Component plugins.
 */
trait AFrameFormatterTrait {

  /**
   *
   */
  public static function globalDefaultSettings() {
    $defaults['aframe_components'] = [];

    return $defaults;
  }

  /**
   *
   */
  protected function globalSettingsForm(array $form, FormStateInterface $form_state) {
    $element['aframe_components'] = [
      '#type'   => 'details',
      '#title'  => t('Additional components')
    ];

    /** @var \Drupal\aframe\AFrameComponentPluginManager $component_manager */
    $component_manager = $this->componentManager;
    $components = $component_manager->getDefinitions();
    foreach ($components as $component) {
      if (isset($this->getSetting('aframe_components')[$component['id']])) {
        /** @var \Drupal\aframe\AFrameComponentPluginInterface $component_instance */
        $component_instance = $component_manager->createInstance($component['id'], [
          'settings' => [
            $component['id'] => $this->getSetting('aframe_components')[$component['id']],
          ],
        ]);
        $element['aframe_components'][$component['id']] = $component_instance->settingsForm($form, $form_state);
      }
    }

    return $element;
  }

  /**
   *
   */
  public function callbackComponentAjax(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    array_pop($parents);
    return NestedArray::getValue($form, $parents);
  }

  /**
   *
   */
  protected function globalSettingsSummary() {
    $summary = [];

    /** @var \Drupal\aframe\AFrameComponentPluginManager $component_manager */
    $component_manager = $this->componentManager;
    $components = $component_manager->getDefinitions();
    foreach ($components as $component) {
      if (isset($this->getSetting('aframe_components')[$component['id']])) {
        /** @var \Drupal\aframe\AFrameComponentPluginInterface $component_instance */
        $component_instance = $component_manager->createInstance($component['id'], [
          'settings' => [
            $component['id'] => $this->getSetting('aframe_components')[$component['id']],
          ],
        ]);
        if ($component_instance->settingsSummary()) {
          $summary[] = $component_instance->settingsSummary();
        }
      }
    }

//    return $summary;
    return [];
  }

  /**
   *
   */
  protected function getAttributes() {
    $attributes = [];

    /** @var \Drupal\aframe\AFrameComponentPluginManager $component_manager */
    $component_manager = $this->componentManager;
    $components = $component_manager->getDefinitions();
    foreach ($components as $component) {
      if (isset($this->getSetting('aframe_components')[$component['id']])) {
        /** @var \Drupal\aframe\AFrameComponentPluginInterface $component_instance */
        $component_instance = $component_manager->createInstance($component['id'], [
          'settings' => [
            $component['id'] => $this->getSetting('aframe_components')[$component['id']],
          ],
        ]);
        $value = $component_instance->getValue();
        if ($value) {
          $attributes[$component['id']] = $value;
        }
      }
    }

    return $attributes;
  }

}
