<?php

namespace Drupal\smart_content_browser\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

class BrowserDerivative extends DeriverBase {

  /**
   * @inheritdoc
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [
      'language' => [
          'label' => 'Language',
          'type' => 'textfield',
        ] + $base_plugin_definition,
      'mobile' => [
          'label' => 'Mobile',
          'type' => 'boolean',
          'weight' => -5,
        ] + $base_plugin_definition,
      'platform_os' => [
          'label' => 'Operating System',
          'type' => 'textfield',
        ] + $base_plugin_definition,
      'cookie' => [
          'label' => 'Cookie',
          'type' => 'key_value',
          'unique' => true,
        ] + $base_plugin_definition,
      'cookie_enabled' => [
          'label' => 'Cookie Enabled',
          'type' => 'boolean',
        ] + $base_plugin_definition,
      'localstorage' => [
          'label' => 'localStorage',
          'type' => 'key_value',
          'unique' => true,
        ] + $base_plugin_definition,
      'width' => [
          'label' => 'Width',
          'type' => 'number',
          'format_options' => [
            'suffix' => 'px'
          ]
        ] + $base_plugin_definition,
      'height' => [
          'label' => 'Height',
          'type' => 'number',
          'format_options' => [
            'suffix' => 'px'
          ]
        ] + $base_plugin_definition,
    ];
    return $this->derivatives;
  }

}
