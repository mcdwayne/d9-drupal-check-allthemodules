<?php

namespace Drupal\ckeditor_config\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "customconfig" plugin.
 *
 * @CKEditorPlugin(
 *   id = "customconfig",
 *   label = @Translation("CKEditor custom configuration")
 * )
 */
class CustomConfig extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, CKEditorPluginContextualInterface {

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $config = [];
    $settings = $editor->getSettings();
    if (!isset($settings['plugins']['customconfig']['ckeditor_custom_config'])) {
      return $config;
    }

    $custom_config = $settings['plugins']['customconfig']['ckeditor_custom_config'];

    // Check if custom config is populated.
    if (!empty($custom_config)) {
      // Build array from string.
      $config_array = preg_split('/\R/', $custom_config);

      // Loop through config lines and append to editorSettings.
      foreach ($config_array as $value) {
        $exploded_value = explode(" = ", $value);

        // Convert true/false strings to boolean values.
        if (strcasecmp($exploded_value[1], 'true') == 0
          || strcasecmp($exploded_value[1], 'false') == 0
          ) {
          $exploded_value[1] = filter_var($exploded_value[1], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        // Convert numeric values to integers.
        if (is_numeric($exploded_value[1])) {
          $exploded_value[1] = (int) $exploded_value[1];
        }
        $config[$exploded_value[0]] = $exploded_value[1];
      }
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {

    $config = ['ckeditor_custom_config' => ''];
    $settings = $editor->getSettings();
    if (isset($settings['plugins']['customconfig'])) {
      $config = $settings['plugins']['customconfig'];
    }

    // Load Editor settings.
    $settings = $editor->getSettings();

    $form['ckeditor_custom_config'] = [
      '#type' => 'textarea',
      '#title' => $this->t('CKEditor Custom Configuration'),
      '#default_value' => $config['ckeditor_custom_config'],
      '#description' => $this->t('Each line may contain a CKEditor configuration setting formatted as "<code>[setting.name] = [value]</code>". See <a href="@url">https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html</a> for more details.<br>E.g. "<code>forcePasteAsPlainText = true</code>" and "<code>forceSimpleAmpersand = true</code>"', ['@url' => 'https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html']),
      '#attached' => [
        'library' => ['ckeditor_config/ckeditor_config.customconfig'],
      ],
      '#element_validate' => [
        [$this, 'validateCustomConfig'],
      ],
    ];

    return $form;
  }

  /**
   * Custom validator for the "custom_config" element in settingsForm().
   */
  public function validateCustomConfig(array $element, FormStateInterface $form_state) {
    // Convert submitted value into an array. Return is empty.
    $config_value = $element['#value'];
    if (empty($config_value)) {
      return;
    }
    $config_array = preg_split('/\R/', $config_value);

    // Loop through lines.
    $i = 1;
    foreach ($config_array as $value) {
      // Check that syntax matches "[something] = [something]".
      preg_match('/(.*?) \= (.*)/', $value, $matches);
      if (empty($matches)) {
        $form_state->setError($element, $this->t('The configuration syntax on line @line is incorrect.', ['@line' => $i]));
      }
      $i++;
    }
  }

}
