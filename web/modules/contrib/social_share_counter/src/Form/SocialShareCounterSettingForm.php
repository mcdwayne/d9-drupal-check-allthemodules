<?php

namespace Drupal\social_share_counter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a settings for SocialShareCounter module.
 */
class SocialShareCounterSettingForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['social_share_counter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_share_counter_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load module's configuration.
    $config = $this->config('social_share_counter.settings');
    $form['#attached']['library'][] = 'social_share_counter/social_share_counter.admin';
    $form['general_setting'] = array(
      '#type' => 'details',
      '#title' => t('General Setting'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['general_setting']['ssc_status'] = array(
      '#type' => 'radios',
      '#title' => t('Display Buttons'),
      '#options' => array(1 => 'Enable', 2 => 'Disable'),
      '#default_value' => $config->get('ssc_status'),
    );
		$form['general_setting']['ssc_sticky'] = array(
      '#type' => 'radios',
      '#title' => t('Stcky Bar'),
      '#options' => array(1 => 'Enable', 2 => 'Disable'),
      '#default_value' => $config->get('ssc_sticky'),
    );
    $form['general_setting']['ssc_text_below_count'] = array(
      '#type' => 'textfield',
      '#title' => t('Text below Count'),
      '#default_value' => $config->get('ssc_text_below_count'),
      '#size' => 26,
    );
    $form['general_setting']['ssc_min_to_show'] = array(
      '#type' => 'textfield',
      '#title' => t('Minimum Count to Show (Default is Zero)'),
      '#default_value' => $config->get('ssc_min_to_show'),
      '#size' => 26,
    );
    $form['ssc_buttons'] = array(
      '#type' => 'details',
      '#title' => t('Button Options'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['ssc_buttons']['ssc_facebook_button_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Facebook'),
      '#default_value' => $config->get('ssc_facebook_button_text'),
      '#size' => 26,
    );
    $form['ssc_buttons']['ssc_facebook_twitter_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Twitter'),
      '#default_value' => $config->get('ssc_facebook_twitter_text'),
      '#size' => 26,
    );

    $form['ssc_placement'] = array(
      '#type'  => 'details',
      '#title' => t('Placement'),
      '#collapsible'  => TRUE,
      '#collapsed'    => FALSE,
    );

    // Add checkboxes for each view mode of each bundle.
    $entity_info = node_type_get_names();
    $view_modes = \Drupal::entityManager()->getViewModes('node');
    $modes = array();
    foreach ($view_modes as $mode => $mode_info) {
      $modes[$mode] = $mode_info['label'];
    }

    // Get a list of content types and view modes.
    $view_modes_selected = array();
    foreach ($entity_info as $bundle => $bundle_label) {
      $form['ssc_placement']['ssc_' . $bundle . '_options'] = array(
        '#title' => t('%label View Modes', array('%label' => $bundle_label)),
        '#description' => t('Select which view modes the Social Share Counter buttons should appear on for %label nodes.', array('%label' => $bundle_label)),
        '#type' => 'checkboxes',
        '#options' => $modes,
        '#default_value' => $config->get('ssc_' . $bundle . '_options'),
      );
      $view_modes_selected = array();
    }

    $form['ssc_placement']['ssc_display_weight'] = array(
      '#type' => 'weight',
      '#title' => t('Content weight'),
      '#required' => FALSE,
      '#delta'         => 50,
      '#default_value' => $config->get('ssc_display_weight'),
      '#description'   => t('Optional weight value determines the location on the page where it will appeared in the <strong>content</strong> section.'),
    );

    return parent::buildForm($form, $form_state);
  }

/**
   * {@inheritdoc}
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $setting_config = $this->config('social_share_counter.settings');
    $entity_info = node_type_get_names();
    $setting_config
      ->set('ssc_status', $values['ssc_status'])
		  ->set('ssc_sticky', $values['ssc_sticky'])
      ->set('ssc_text_below_count', $values['ssc_text_below_count'])
      ->set('ssc_min_to_show', $values['ssc_min_to_show'])
      ->set('ssc_facebook_button_text', $values['ssc_facebook_button_text'])
      ->set('ssc_facebook_twitter_text', $values['ssc_facebook_twitter_text']);
    foreach ($entity_info as $bundle => $bundle_label) {
      $setting_config->set('ssc_' . $bundle . '_options', $values['ssc_' . $bundle . '_options']);
    }
    $setting_config->set('ssc_display_weight', $values['ssc_display_weight']);
    $setting_config->save();

    parent::submitForm($form, $form_state);
  }

}
