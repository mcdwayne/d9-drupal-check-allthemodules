<?php

namespace Drupal\doubleclick_floodlight\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DoubleclickSettingsForm.
 * @package Drupal\doubleclick_floodlight\Form
 */
class DoubleclickSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'doubleclick_floodlight.DoubleclickSettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'doubleclick_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('doubleclick_floodlight.DoubleclickSettings');


    // Required settings.
    $form['main_tag_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Main Tag Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#group' => 'settings',
    ];

    $form['main_tag_settings']['doubleclick_floodlight_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable / Disable'),
      '#default_value' => $config->get('doubleclick_floodlight_enabled'),
      '#description' => t('Sets if the tag will be showing or will be hidden.'),
    ];

    $form['main_tag_settings']['doubleclick_floodlight_account_id'] = [
      '#type' => 'textfield',
      '#title' => t('Account ID'),
      '#default_value' => $config->get('doubleclick_floodlight_account_id'),
      '#required' => TRUE,
      '#description' => t('Floodlight account id.'),
    ];

    $form['main_tag_settings']['doubleclick_floodlight_show_standard'] = [
      '#type' => 'checkbox',
      '#title' => t('Show Standard Tracking pixel'),
      '#default_value' => $config->get('doubleclick_floodlight_show_standard'),
      '#description' => t('Standard tracking pixel will be displayed if enabled.'),
    ];

    $form['main_tag_settings']['doubleclick_floodlight_show_unique'] = [
      '#type' => 'checkbox',
      '#title' => t('Show Unique Tracking pixel'),
      '#default_value' => $config->get('doubleclick_floodlight_show_unique'),
      '#description' => t('Unique tracking pixel will be displayed if enabled.'),
    ];

    // Optional parameters.
    $form['optional_tag_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Optional Tag Parameters'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#group' => 'settings',
    ];

    $form['optional_tag_settings']['doubleclick_floodlight_type'] = [
      '#type' => 'textfield',
      '#title' => t('Type - Group Tag String'),
      '#required' => FALSE,
      '#maxlength' => 255,
      '#default_value' => $config->get('doubleclick_floodlight_type'),
      '#description' => t('Group Tag String'),
    ];

    $form['optional_tag_settings']['doubleclick_floodlight_cat'] = [
      '#type' => 'textfield',
      '#title' => t('Cat - Activity Tag String'),
      '#default_value' => $config->get('doubleclick_floodlight_cat'),
      '#description' => t('Activity Tag String'),
    ];


    $default_theme = \Drupal::config('system.theme')->get('default');
    // Apply only to new theme's visible regions.
    $regions = system_region_list($default_theme, REGIONS_ALL);
    $form['optional_tag_settings']['doubleclick_floodlight_region'] = [
      '#type' => 'select',
      '#options' => $regions,
      '#title' => t('Region where pixels will be added.'),
      '#default_value' => $config->get('doubleclick_floodlight_region'),
      '#description' => t('Select region which is first after the opening <body> tag.'),
    ];


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('doubleclick_floodlight.DoubleclickSettings')
      ->set('doubleclick_floodlight_enabled', $form_state->getValue('doubleclick_floodlight_enabled'))
      ->set('doubleclick_floodlight_account_id', $form_state->getValue('doubleclick_floodlight_account_id'))
      ->set('doubleclick_floodlight_show_standard', $form_state->getValue('doubleclick_floodlight_show_standard'))
      ->set('doubleclick_floodlight_show_unique', $form_state->getValue('doubleclick_floodlight_show_unique'))
      ->set('doubleclick_floodlight_type', $form_state->getValue('doubleclick_floodlight_type'))
      ->set('doubleclick_floodlight_cat', $form_state->getValue('doubleclick_floodlight_cat'))
      ->set('doubleclick_floodlight_region', $form_state->getValue('doubleclick_floodlight_region'))
      ->save();
  }

}
