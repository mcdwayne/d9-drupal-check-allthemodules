<?php

/**
 * @file
 * Definition of \Drupal\ckeditor\Plugin\editor\editor\CKEditor.
 */

namespace Drupal\ckeditor\Plugin\editor\editor;

use Drupal\editor\Plugin\EditorBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\editor\Plugin\Core\Entity\Editor;

/**
 * Defines a CKEditor-based text editor for Drupal.
 *
 * @Plugin(
 *   id = "ckeditor",
 *   label = @Translation("CKEditor"),
 *   module = "ckeditor"
 * )
 */
class CKEditor extends EditorBase {

  /**
   * Implements \Drupal\editor\Plugin\EditorInterface::getDefaultSettings().
   */
  function getDefaultSettings() {
    return array(
      'toolbar' => array(
        'buttons' => array(
          array(
            'Source', '|', 'Bold', 'Italic', '|',
            'NumberedList', 'BulletedList', 'Blockquote', '|',
            'JustifyLeft', 'JustifyCenter', 'JustifyRight', '|',
            'Link', 'Unlink', '|', 'Image', 'Maximize',
          ),
        ),
        'format_list' => array('p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'),
        'style_list' => array(),
      ),
    );
  }

  /**
   * Implements \Drupal\editor\Plugin\EditorInterface::settingsForm().
   */
  function settingsForm(array $form, array &$form_state, Editor $editor) {
    $module_path = drupal_get_path('module', 'ckeditor');
    $plugins = ckeditor_plugins();

    $form['toolbar'] = array(
      '#type' => 'fieldset',
      '#title' => t('Toolbar'),
      '#attached' => array(
        'library' => array(array('ckeditor', 'drupal.ckeditor.admin')),
        'js' => array(
          array('data' => array('ckeditor' => array('toolbarAdmin' => theme('ckeditor_settings_toolbar', array('editor' => $editor, 'plugins' => $plugins)))), 'type' => 'setting')
        ),
      ),
      '#attributes' => array('class' => array('ckeditor-toolbar-configuration')),
    );
    $form['toolbar']['buttons'] = array(
      '#type' => 'textarea',
      '#title' => t('Toolbar buttons'),
      '#default_value' => json_encode($editor->settings['toolbar']['buttons']),
      '#attributes' => array('class' => array('ckeditor-toolbar-textarea')),
    );
    $form['toolbar']['format_list'] = array(
      '#type' => 'textfield',
      '#title' => t('Format list'),
      '#default_value' => implode(', ', $editor->settings['toolbar']['format_list']),
      '#description' => t('A list of tags that will be provided in the "Format" dropdown, separated by commas.')
    );
    $form['toolbar']['style_list'] = array(
      '#type' => 'textarea',
      '#title' => t('Style list'),
      '#rows' => 4,
      '#default_value' => implode("\n", $editor->settings['toolbar']['style_list']),
      '#description' => t('A list of classes that will be provided in the "Styles" dropdown, each on a separate line. These styles should be available in your theme\'s editor.css as well as in your theme\'s main CSS file.')
    );

    return $form;
  }

  /**
   * Implements \Drupal\editor\Plugin\EditorInterface::settingsFormSubmit().
   */
  function settingsFormSubmit(array $form, array &$form_state) {
    // Modify the toolbar settings by reference. The values in
    // $form_state['values']['editor_settings'] will be saved directly by
    // editor_form_filter_admin_format_submit().
    $toolbar_settings = &$form_state['values']['editor_settings']['toolbar'];

    $toolbar_settings['buttons'] = json_decode($toolbar_settings['buttons'], FALSE);

    $format_list = array();
    foreach (explode(',', $toolbar_settings['format_list']) as $format) {
      $format_list[] = trim($format);
    }
    $toolbar_settings['format_list'] = $format_list;

    $style_list = array();
    foreach (explode(',', $toolbar_settings['style_list']) as $style) {
      $style_list[] = trim($style);
    }
    $toolbar_settings['style_list'] = $style_list;
  }

  /**
   * Implements \Drupal\editor\Plugin\EditorInterface::getJSSettings().
   */
  function getJSSettings(Editor $editor) {
    global $language;

    // Loop through all available plugins and check to see if it has been
    // explicitly enabled. At the same time, associate each plugin with its
    // buttons (if any) so we can check if the plugin should be enabled implicitly
    // based on the toolbar.
    $plugin_info = ckeditor_plugins();
    $external_plugins = array();
    $external_css = array();
    $all_buttons = array();
    foreach ($plugin_info as $plugin_name => $plugin) {
      // Check if this plugin should be enabled.
      if (isset($plugin['enabled callback'])) {
        if ($plugin['enabled callback'] === TRUE || $plugin['enabled callback']($editor) && !empty($plugin['path'])) {
          $external_plugins[$plugin_name]['file'] = $plugin['file'];
          $external_plugins[$plugin_name]['path'] = $plugin['path'];
          if (isset($plugin['css'])) {
            $external_css = array_merge($external_css, $plugin['css']);
          }
        }
      }
      // Associate each plugin with its button.
      if (isset($plugin['buttons'])) {
        if (empty($plugin['internal'])) {
          foreach ($plugin['buttons'] as $button_name => &$button) {
            $button['plugin'] = $plugin;
            $button['plugin']['name'] = $plugin_name;
            unset($button['plugin']['buttons']);
          }
        }
        $all_buttons = array_merge($all_buttons, $plugin['buttons']);
      }
    }

    // Change the toolbar separators into groups and record needed plugins based
    // on use in the toolbar.
    $toolbar = array();
    foreach ($editor->settings['toolbar']['buttons'] as $row_number => $row) {
      $button_group = array();
      foreach ($row as $button_name) {
        if ($button_name === '|') {
          $toolbar[] = $button_group;
          $button_group = array();
        }
        else {
          // Sanity check that the button exists in our installation.
          if (isset($all_buttons[$button_name])) {
            $button_group['items'][] = $button_name;

            // Keep track of the needed plugin for this button, if any.
            if (isset($all_buttons[$button_name]['plugin']['path'])) {
              $plugin_name = $all_buttons[$button_name]['plugin']['name'];
              $external_plugin = $all_buttons[$button_name]['plugin'];
              $external_plugins[$plugin_name]['file'] = $external_plugin['file'];
              $external_plugins[$plugin_name]['path'] = $external_plugin['path'];
              if (isset($external_plugin['css'])) {
                $external_css = array_merge($external_css, $external_plugin['css']);
              }
            }
          }
        }
      }
      $toolbar[] = $button_group;
      $toolbar[] = '/';
    }

    // Collect a list of CSS files to be added to the editor instance.
    $css = array(
      drupal_get_path('module', 'ckeditor') . '/css/ckeditor.css',
      drupal_get_path('module', 'ckeditor') . '/css/ckeditor-iframe.css',
    );
    $css = array_merge($css, $external_css, _ckeditor_theme_css());
    drupal_alter('ckeditor_css', $css, $editor, $format);

    // Convert all paths to be relative to root.
    foreach ($css as $key => $css_path) {
      $css[$key] = base_path() . $css_path;
    }

    // Initialize reasonable defaults that provide expected basic behavior.
    $settings = array(
      'toolbar' => $toolbar,
      'extraPlugins' => implode(',', array_keys($external_plugins)),
      'removePlugins' => 'image',
      //'forcePasteAsPlainText' => TRUE,
      'contentsCss' => array_values($css),
      'pasteFromWordPromptCleanup' => TRUE,
      'indentClasses' => array('indent1', 'indent2', 'indent3'),
      'justifyClasses' => array('align-left', 'align-center', 'align-right', 'align-justify'),
      'coreStyles_underline' => array('element' => 'span', 'attributes' => array('class' => 'underline')),
      'format_tags' => implode(';', $editor->settings['toolbar']['format_list']),
      'removeDialogTabs' => 'image:Link;image:advanced;link:advanced',
      'language' => isset($language->language) ? $language->language : '',
      'resize_dir' => 'vertical',
    );

    // These settings are used specifically by Drupal.
    $settings['externalPlugins'] = $external_plugins;

    return $settings;
  }

  /**
   * Implements \Drupal\editor\Plugin\EditorInterface::getLibraries().
   */
  function getLibraries(Editor $editor) {
    return array(
      array('ckeditor', 'drupal.ckeditor'),
    );
  }
}
