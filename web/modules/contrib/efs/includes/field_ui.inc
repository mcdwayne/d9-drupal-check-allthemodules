<?php

/**
 * @file
 * Display entity form alterations.
 */

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\efs\Entity\ExtraField;
use Drupal\field_ui\Form\EntityDisplayFormBase;

/**
 * Function to alter the display overview screens.
 *
 * @param array $form
 *   The array form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state object.
 */
function efs_field_ui_display_form_alter(array &$form, FormStateInterface $form_state) {
  // Only start altering the form if we need to.
  if (empty($form['#fields']) && empty($form['#extra'])) {
    return;
  }

  $callback_object = $form_state->getBuildInfo()['callback_object'];
  if (!$callback_object instanceof EntityDisplayFormBase) {
    throw new InvalidArgumentException('Unkown callback object.');
  }

  /** @var \Drupal\Core\Entity\EntityDisplayBase $display */
  $display = $callback_object->getEntity();

  $params = efs_field_ui_form_params($display);
  $form['#context'] = $display;

  $table = &$form['fields'];
  $form_state_values = $form_state->getValues();
  $extra_fields_keys = array_flip($form['#extra']);

  $extra_fields = efs_get_extra_fields($params->entity_type, $params->bundle, $params->context);
  foreach (Element::children($table) as $name) {
    if (empty($extra_fields[$name])) {
      continue;
    }

    // If mode is different then delete field.
    if ($extra_fields[$name]['mode'] != $params->mode) {
      unset($table[$name]);
      // Unset the extra field key from the form to prevent an
      // 'Undefined index' error when saving the display.
      // @see \Drupal\field_ui\Form\EntityDisplayFormBase::getExtraFields
      // @see \Drupal\field_ui\Form\EntityDisplayFormBase::copyFormValuesToEntity
      unset($form['#extra'][$extra_fields_keys[$name]]);
      continue;
    }

    $id = $extra_fields[$name]['id'];
    $extra_field = ExtraField::load($id);

    $base_button = [
      '#submit' => [
        [
          $form_state->getBuildInfo()['callback_object'],
          'multistepSubmit',
        ],
      ],
      '#ajax' => [
        'callback' => [
          $form_state->getBuildInfo()['callback_object'],
          'multistepAjax',
        ],
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
      ],
      '#field_name' => $name,
    ];

    // Save the extra field when the configuration is submitted.
    if (!empty($form_state_values[$name . '_plugin_settings_update'])) {
      efs_formatter_settings_update($extra_field, $form_state_values['fields'][$name]);
    }

    if ($form_state->get('plugin_settings_edit') == $name) {
      $settings = efs_format_settings_form($extra_field, $form, $form_state, $name);
      $table[$name]['format']['#cell_attributes'] = ['colspan' => 2];
      $save_button = [
        '#type' => 'submit',
        '#name' => $name . '_plugin_settings_update',
        '#value' => t('Update'),
        '#op' => 'update',
      ];
      $cancel_button = [
        '#type' => 'submit',
        '#name' => $name . '_plugin_settings_cancel',
        '#value' => t('Cancel'),
        '#op' => 'cancel',
        // Do not check errors for the 'Cancel' button.
        '#limit_validation_errors' => [],
      ];
      $table[$name]['format']['format_settings'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['field-plugin-settings-edit-form']],
        '#parents' => ['fields', $name, 'settings_edit_form'],
        '#weight' => -5,
        'label' => [
          '#markup' => t('Extra field format:') . ' <span class="formatter-name">' . efs_get_plugin_label($extra_field) . '</span>',
        ],
        // Create a settings form where hooks can pick in.
        'settings' => $settings,
        'actions' => [
          '#type' => 'actions',
          'save_settings' => $base_button + $save_button,
          'cancel_settings' => $base_button + $cancel_button,
        ],
      ];
      $table[$name]['#attributes']['class'][] = 'field-formatter-settings-editing';
      $table[$name]['format']['type']['#attributes']['class'] = ['visually-hidden'];
      unset($table[$name]['plugin']);
      unset($table[$name]['settings_summary']);
      unset($table[$name]['settings_edit']);
    }
    else {
      // After saving, the settings are updated here aswell. First we create
      // the element for the table cell.
      $table[$name]['settings_summary'] = efs_format_settings_summary($extra_field);
      // Add the configure button.
      $config = [
        '#type' => 'image_button',
        '#name' => $name . '_group_settings_edit',
        '#src' => 'core/misc/icons/787878/cog.svg',
        '#attributes' => [
          'class' => ['field-plugin-settings-edit'],
          'alt' => t('Edit'),
        ],
        '#op' => 'edit',
        // Do not check errors for the 'Edit' button, but make sure we get
        // the value of the 'plugin type' select.
        '#limit_validation_errors' => [['fields', $name, 'type']],
        '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
        '#suffix' => '</div>',
      ];
      $table[$name]['settings_edit'] = $base_button + $config;

      $current_url = Url::fromRoute('<current>');
      $query = ['destination' => $current_url->toString()];
      $delete_route = Url::fromRoute('entity.extra_field.delete_form', ['extra_field' => $id], ['query' => $query]);

      $table[$name]['settings_edit']['#suffix'] .= Drupal::l(t('delete'), $delete_route);
    }
  }
}

/**
 * Get de selected plugin label por the settings form.
 *
 * @param \Drupal\efs\Entity\ExtraField $extra_field
 *   The extra field entity.
 *
 * @return string
 *   The plugin label.
 */
function efs_get_plugin_label(ExtraField $extra_field) {
  $manager = \Drupal::service('plugin.manager.efs.formatters');
  $plugin_id = $extra_field->getPlugin();
  /** @var \Drupal\efs\ExtraFieldFormatterPluginBase $plugin */
  $plugin = $manager->createInstance($plugin_id);
  $def = $plugin->getPluginDefinition();
  return $def['label'];
}

/**
 * Creates a form for efs formatters.
 *
 * @param \Drupal\efs\Entity\ExtraField $extra_field
 *   The extra field entity.
 * @param array $form
 *   The form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state object.
 * @param string $name
 *   The name of field.
 *
 * @return array
 *   The settings array form.
 */
function efs_format_settings_form(ExtraField $extra_field, array $form, FormStateInterface $form_state, string $name) {
  $manager = \Drupal::service('plugin.manager.efs.formatters');
  $plugin_id = $extra_field->getPlugin();
  /** @var \Drupal\efs\ExtraFieldFormatterPluginBase $plugin */
  $plugin = $manager->createInstance($plugin_id);
  $settings = $extra_field->getSettings();
  if (empty($settings)) {
    $settings = $plugin->defaultContextSettings($extra_field->getContext());
  }
  $plugin->setSettings($settings);

  $build_info = $form_state->getBuildInfo();
  return $plugin->settingsForm($build_info['callback_object'], $form, $form_state, $extra_field, $name);
}

/**
 * Creates a summary for the field format configuration summary.
 *
 * @param \Drupal\efs\Entity\ExtraField $extra_field
 *   The extra field entity.
 *
 * @return array
 *   The summary array.
 */
function efs_format_settings_summary(ExtraField $extra_field) {

  $manager = \Drupal::service('plugin.manager.efs.formatters');
  $plugin_id = $extra_field->getPlugin();
  /** @var \Drupal\efs\ExtraFieldFormatterPluginBase $plugin */
  $plugin = $manager->createInstance($plugin_id);

  $settings = $extra_field->getSettings();
  $context = $extra_field->getContext();
  if (empty($settings)) {
    $settings = $plugin->defaultContextSettings($context);
  }
  $plugin->setSettings($settings);
  $summary = $plugin->settingsSummary($context);

  return [
    '#markup' => '<div class="field-plugin-summary">' . implode('<br />', $summary) . '</div>',
    '#cell_attributes' => ['class' => ['field-plugin-summary-cell']],
  ];
}

/**
 * Helper function to get the form parameters.
 *
 * To use while building the fields and display overview form.
 *
 * @param \Drupal\Core\Entity\EntityDisplayBase $display
 *   The display entity.
 *
 * @return \stdClass
 *   The params as object.
 */
function efs_field_ui_form_params(EntityDisplayBase $display) {
  $params = new stdClass();
  $params->entity_type = $display->getTargetEntityTypeId();
  $params->bundle = $display->getTargetBundle();
  $params->mode = $display->getMode();
  $params->context = efs_get_context_from_display($display);

  return $params;
}

/**
 * Helper function to get context from entity display.
 *
 * @param \Drupal\Core\Entity\EntityDisplayBase $display
 *   The display entity.
 *
 * @return string
 *   The context.
 */
function efs_get_context_from_display(EntityDisplayBase $display) {
  if ($display instanceof EntityFormDisplayInterface) {
    return 'form';
  }
  elseif ($display instanceof EntityViewDisplayInterface) {
    return 'display';
  }

  throw new LogicException('Unknown display object.');
}

/**
 * Get extra field.
 *
 * @param string $entity_type
 *   The entity type.
 * @param string $bundle
 *   The bundle of entity.
 * @param string $context
 *   The context form or display.
 *
 * @return array
 *   The extra field array.
 */
function efs_get_extra_fields(string $entity_type, string $bundle, string $context) {
  $extra_fields = efs_entity_extra_field_info();
  if (!empty($extra_fields[$entity_type][$bundle][$context])) {
    return $extra_fields[$entity_type][$bundle][$context];
  }
  return NULL;
}

/**
 * Update handler for efs configuration settings.
 *
 * @param \Drupal\efs\Entity\ExtraField $extra_field
 *   The extra field entity.
 * @param array $settings
 *   The field settings.
 */
function efs_formatter_settings_update(ExtraField $extra_field, array $settings) {
  if (!empty($settings['settings_edit_form']['settings']['weight'])) {
    $weight = $settings['settings_edit_form']['settings']['weight'];
    $extra_field->set('weight', $weight);
    unset($settings['settings_edit_form']['settings']['weight']);
  }

  $extra_field->setSettings($settings['settings_edit_form']['settings']);
  $extra_field->save();
}
