<?php

namespace Drupal\alertbox\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\block\BlockInterface;

/**
 * Configure example settings for this site.
 */
class AlertboxSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'alertbox_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('alertbox.settings');
    /** @var \Drupal\Core\Extension\ThemeHandler $theme_handler */
    $theme_handler = \Drupal::service('theme_handler');

    $form['alertbox_interface_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Interface options'),
      '#weight' => -10,
      '#collapsible' => FALSE,
    ];
    // Add a checkbox to enable/disable a Close button on alertboxs.
    $form['alertbox_interface_options']['alertbox_allow_hide'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow user to hide alert boxes'),
      '#default_value' => $config->get('alertbox_allow_hide'),
      '#description' => $this->t('By checking this option a cookie will be stored at user side to keep a hidden state.'),
    ];
    // Add a checkbox to enable/disable the Drupal default block placement system.
    $form['alertbox_interface_options']['alertbox_default_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Drupal default block management'),
      '#default_value' => $config->get('alertbox_default_mode'),
      '#description' => $this->t('Use the Drupal default block management system instead of using the simpler block placement included in this module.'),
    ];
    // Field added by alertbox_modal. Check the module for more info.
    $form['alertbox_interface_options']['alertbox_show_modal'] = [
      '#type' => 'checkbox',
      '#title' => t('Display alert boxes on a modal overlay'),
      '#description' => $this->t('You must enable Alertbox Modal to use this option.'),
      '#disabled' => TRUE,
    ];

    // Define labels for close and dismiss buttons.
    $form['alertbox_interface_options']['alertbox_label_close'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Close button label'),
      '#default_value' => $config->get('alertbox_label_close'),
      '#description' => $this->t('Define the label that will be used for the close button.'),
      '#states' => [
        'visible' => [
          ':input[name="alertbox_show_modal"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['alertbox_interface_options']['alertbox_label_dismiss'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dismiss button label'),
      '#default_value' => $config->get('alertbox_label_dismiss'),
      '#description' => $this->t('Define the label that will be used for the dismiss button.'),
      '#states' => [
        'visible' => [
          ':input[name="alertbox_show_modal"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Get default active theme.
    $default_theme_key = $theme_handler->getDefault();
    // Get theme regions.
    $default_theme_regions = system_region_list($default_theme_key, REGIONS_VISIBLE);
    $default_value = $config->get('alertbox_' . $default_theme_key . '_region');
    $form['alertbox_' . $default_theme_key . '_region'] = [
      '#type' => 'select',
      '#title' => $this->t('Pre-defined region for @themename theme', ['@themename' => $theme_handler->getName($default_theme_key)]),
      '#options' => $default_theme_regions,
      '#default_value' => $default_value ? $default_value : 'content',
      '#description' => $this->t('Set the default theme region where Alertbox blocks should be assigned to.'),
      '#states' => [
        'visible' => [
          [':input[name="alertbox_default_mode"]' => ['checked' => FALSE]]
        ]
      ]
    ];

    // Let's create a fieldset to define non-default theme regions.
    // These regions are disabled by default.
    $form['alertbox_theme_regions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Other themes'),
      '#weight' => 3,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => $this->t('Set each theme region where Alertbox blocks should be assigned to.'),
      '#states' => [
        'visible' => [
          [':input[name="alertbox_default_mode"]' => ['checked' => FALSE]]
        ]
      ]
    ];
    $theme_list = $theme_handler->listInfo();
    foreach ($theme_list as $theme_key => $theme_info) {
      if ($theme_key == $default_theme_key) {
        continue;
      }
      $theme_regions = system_region_list($theme_key, REGIONS_VISIBLE);
      $theme_regions[-1] = $this->t('Disabled');
      $default_value = $config->get('alertbox_' . $theme_key . '_region');
      $form['alertbox_theme_regions']['alertbox_' . $theme_key . '_region'] = [
        '#type' => 'select',
        '#title' => $this->t('Pre-defined region for @themename theme', ['@themename' => $theme_info->info['name']]),
        '#options' => $theme_regions,
        '#default_value' => $default_value ? $default_value : BlockInterface::BLOCK_REGION_NONE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('alertbox.settings');
    foreach ($form_state->getValues() as $field_name => $field_value) {
      if (strpos($field_name, 'alertbox_') === 0) {
        $config->set($field_name, $field_value)->save();

        // Let's change field_alertbox_visibility entity form visibility based
        // on the configuration settings.
        if ($field_name == 'alertbox_default_mode') {
          $alertbox_visibility = \Drupal::configFactory()
            ->getEditable('core.entity_form_display.block_content.alertbox.default');
          if ($field_value == TRUE) {
            $alertbox_visibility->set('content.field_alertbox_visibility', NULL);
            $alertbox_visibility->set('hidden.field_alertbox_visibility', TRUE);
          }
          else {
            // Setting the default settings. Maybe there's a better way but...
            $alertbox_visibility->set('content.field_alertbox_visibility.weight', 11);
            $alertbox_visibility->set('content.field_alertbox_visibility.settings', []);
            $alertbox_visibility->set('content.field_alertbox_visibility.third_party_settings', []);
            $alertbox_visibility->set('content.field_alertbox_visibility.type', 'options_buttons');
            $alertbox_visibility->set('content.field_alertbox_visibility.region', 'content');
            $alertbox_visibility->set('hidden', []);
          }
          $alertbox_visibility->save();
        }
      }
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'alertbox.settings',
    ];
  }

}
