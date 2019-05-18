<?php

namespace Drupal\ckeditor_confighelper\Plugin\CKEditorPlugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Defines the CKEditor Configuration Helper plugin.
 *
 * No buttons are exposed for this plugin, it is only here so it gets properly
 * loaded by Drupal.
 *
 * @CKEditorPlugin(
 *   id = "confighelper",
 *   label = @Translation("CKEditor Configuration Helper plugin"),
 * )
 */
class ConfigHelper extends PluginBase implements CKEditorPluginContextualInterface, CKEditorPluginConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
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
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    $path = 'libraries/confighelper';
    if (\Drupal::moduleHandler()->moduleExists('libraries')) {
      $path = libraries_get_path('confighelper');
    }
    return $path . '/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $settings = $editor->getSettings();

    $configs_map = [
      'dialog_default_values' => 'dialogFieldsDefaultValues',
      'remove_dialog' => 'removeDialogFields',
      'hide_dialog' => 'hideDialogFields',
      'placeholder' => 'placeholder',
    ];
    $configs = [];
    foreach ($configs_map as $config => $name) {
      if (!empty($settings['plugins']['confighelper'][$config])) {
        $configs[$name] = $settings['plugins']['confighelper'][$config];
      }
    }
    return $configs;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();
    $default_values_example = new FormattableMarkup('
      <pre>
      {
        image: {
          advanced: {
            txtGenClass : \'myClass\',
            txtGenTitle : \'Image title\'
          }
        }
      }</pre>', []
    );
    $form['dialog_default_values'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Dialog Default Values'),
      '#description' => $this->t('This setting uses directly a JSON object as 
        the configuration value, first an object that has the dialog names as 
        properties, each property is a new object with the name of the tabs and 
        finally each property name maps to the field name and it\'s value is 
        the default value to use for the field.<br />
        Example: @pre', ['@pre' => $default_values_example]
      ),
      '#default_value' => !empty($settings['plugins']['confighelper']['dialog_default_values']) ? $settings['plugins']['confighelper']['dialog_default_values'] : '',
    ];
    $form['remove_dialog'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Remove Dialog Fields'),
      '#description' => $this->t('This entry is a string, the fields are 
        defined as dialogName + ":" + tab + ":" + field. 
        Fields are joined with semicolons.<br /> 
        Example: "image:info:txtBorder;image:info:txtHSpace"'
      ),
      '#default_value' => !empty($settings['plugins']['confighelper']['remove_dialog']) ? $settings['plugins']['confighelper']['remove_dialog'] : '',
    ];
    $form['hide_dialog'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Hide Dialog Fields'),
      '#description' => $this->t('This entry uses the same syntax that 
        the Remove Dialog Fields option. The difference is that some fields 
        can\'t be removed easily as other parts of the dialog might not be 
        ready and might try to always use it, generating a javascript error. 
        In other cases the layout might be broken if the field is removed 
        instead of hidden.'
      ),
      '#default_value' => !empty($settings['plugins']['confighelper']['hide_dialog']) ? $settings['plugins']['confighelper']['hide_dialog'] : '',
    ];
    $form['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Placeholder'),
      '#description' => $this->t('This a text that will be shown when the editor
        is empty following the HTML5 placeholder attribute. When the user focus 
        the editor, the content is cleared automatically'
      ),
      '#default_value' => !empty($settings['plugins']['confighelper']['placeholder']) ? $settings['plugins']['confighelper']['placeholder'] : '',
    ];

    return $form;
  }

}
