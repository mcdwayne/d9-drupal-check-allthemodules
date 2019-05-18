<?php

/**
 * @file
 * Advertising Entity: Fallback - functions for admin pages.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Adds fallback options to the global settings form.
 *
 * @param array &$form
 *   The form array of the ad_entity_settings form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The corresponding form state.
 */
function _ad_entity_fallback_settings_form(array &$form, FormStateInterface $form_state) {
  /** @var \Drupal\ad_entity\Form\GlobalSettingsForm $form_object */
  $form_object = $form_state->getFormObject();
  $config = $form_object->getConfig();
  $fallback_settings = $config->get('fallback');

  $form['common']['fallback'] = [
    '#type' => 'fieldset',
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
    '#title' => t('Fallback'),
    '#tree' => TRUE,
    '#parents' => ['fallback'],
    '#weight' => 30,
    '#description' => t('Fallback Advertisement can be configured at any Advertising entity form.'),
  ];
  $form['common']['fallback']['timeout'] = [
    '#type' => 'number',
    '#min' => 100,
    '#title' => t('Timeout'),
    '#description' => t('Microseconds to wait until a given fallback will be loaded instead of the original.'),
    '#required' => TRUE,
    '#default_value' => !empty($fallback_settings['timeout']) ? $fallback_settings['timeout'] : 1000,
    '#weight' => 10,
  ];
  array_unshift($form['#submit'], '_ad_entity_fallback_settings_form_submit');
}

/**
 * Submit handler for the fallback options in the ad_entity_settings form.
 *
 * @param array $form
 *   The form array of the ad_entity_settings form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The corresponding form state.
 */
function _ad_entity_fallback_settings_form_submit(array $form, FormStateInterface $form_state) {
  /** @var \Drupal\ad_entity\Form\GlobalSettingsForm $form_object */
  $form_object = $form_state->getFormObject();
  $config = $form_object->getConfig();
  $submitted = $form_state->getValue('fallback');
  $fallback_settings = [
    'timeout' => 1000,
  ];
  if (!empty($submitted['timeout'])) {
    $timeout = (int) $submitted['timeout'];
    if (!($timeout < 100)) {
      $fallback_settings['timeout'] = $timeout;
    }
  }
  $config->set('fallback', $fallback_settings);
}

/**
 * Adds fallback options to the Advertising entity form.
 *
 * @param array &$form
 *   The form array of the Advertising entity form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The corresponding form state.
 */
function _ad_entity_fallback_entity_form(array &$form, FormStateInterface $form_state) {
  $ad_entities = \Drupal::entityTypeManager()->getStorage('ad_entity')->loadMultiple();
  $options = [];
  /** @var \Drupal\ad_entity\Entity\AdEntityInterface $ad_entity */
  foreach ($ad_entities as $ad_entity) {
    $has_fallback = (bool) $ad_entity->getThirdPartySetting('ad_entity_fallback', 'ad_entity_id');
    if (!$has_fallback) {
      $options[$ad_entity->id()] = $ad_entity->label();
    }
  }

  /** @var \Drupal\Core\Entity\EntityForm $form_object */
  $form_object = $form_state->getFormObject();
  /** @var \Drupal\ad_entity\Entity\AdEntityInterface $current */
  $current = $form_object->getEntity();
  if (empty($current)) {
    return;
  }
  if (!$current->isNew()) {
    unset($options[$current->id()]);
  }
  if (empty($options)) {
    return;
  }
  $default = $current->getThirdPartySettings('ad_entity_fallback');
  $form['third_party']['fallback'] = [
    '#type' => 'fieldset',
    '#tree' => TRUE,
    '#collapsible' => FALSE,
    '#collapsed' => FALSE,
    '#attributes' => ['id' => 'fallback-form-settings'],
    '#title' => t("Fallback"),
    '#weight' => 100,
    '#parents' => ['third_party_settings', 'ad_entity_fallback'],
  ];
  $form['third_party']['fallback']['ad_entity_id'] = [
    '#type' => 'select',
    '#options' => $options,
    '#title' => t('Advertising entity'),
    '#description' => t('The Advertising entity to use as fallback when the current entity is empty.'),
    '#default_value' => !empty($default['ad_entity_id']) ? $default['ad_entity_id'] : '',
    '#required' => FALSE,
    '#empty_value' => '',
  ];
}
