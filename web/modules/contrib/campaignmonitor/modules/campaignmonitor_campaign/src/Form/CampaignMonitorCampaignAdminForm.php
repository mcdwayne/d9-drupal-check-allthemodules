<?php

namespace Drupal\campaignmonitor_campaign\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure campaignmonitor settings for this site.
 */
class CampaignMonitorCampaignAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'campaignmonitor_campaign_admin_settings';
  }

  /**
   *
   */
  protected function getEditableConfigNames() {
    return ['campaignmonitor_campaign.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('campaignmonitor_campaign.settings');

    $types = node_type_get_types();

    $options = [];
    foreach ($types as $name => $type) {
      $options[$name] = $type->label();
    }

    $default = $config->get('node_types') != NULL ? $config->get('node_types') : [];

    $form['campaignmonitor_types'] = [
      '#type' => 'fieldset',
      '#title' => t('Node Types'),
      '#description' => t('Select node types to use as Campaigns'),
      '#collapsible' => empty($config) ? FALSE : TRUE,
      '#collapsed' => empty($config) ? FALSE : TRUE,
      '#tree' => TRUE,
    ];

    $form['campaignmonitor_types']['node_types'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => t('Node types'),
      '#description' => t('Any selected node types can be used to send Campaigns'),
      '#default_value' => $default,
      '#required' => TRUE,
    ];

    $form['campaignmonitor_custom'] = [
      '#type' => 'fieldset',
      '#title' => t('Custom Settings'),
      '#description' => t('Some optional customizations'),
      '#collapsible' => empty($config) ? FALSE : TRUE,
      '#collapsed' => empty($config) ? FALSE : TRUE,
      '#tree' => TRUE,
    ];
    $form['campaignmonitor_custom']['custom_store'] = [
      '#type' => 'checkbox',
      '#title' => t('Custom Directory'),
      '#description' => t('By default campaign html files are stored in the public files directory.  Select this if
      you want to nominate a custom directory.'),
      '#default_value' => $config->get('custom_store'),
      '#attributes' => ['class' => ['custom-store-checkbox']],
    ];

    $form['campaignmonitor_custom']['custom_directory'] = [
      '#type' => 'textfield',
      '#title' => t('Custom Directory Path'),
      '#description' => t('Enter the path of the directory within Drupal root.'),
      '#default_value' => $config->get('custom_directory'),
      '#states' => [
        'visible' => [
          '.custom-store-checkbox' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['css_library'] = [
      '#type' => 'textfield',
      '#title' => t('CSS library'),
      '#description' => t('Enter the name of the CSS library to use for Campaigns.  This should be in the form
      MODULENAME/LIBRARYNAME.'),
      '#default_value' => $config->get('css_library'),

    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('campaignmonitor_campaign.settings');
    $config
      ->set('custom_store', $form_state->getValue(['campaignmonitor_custom', 'custom_store']))
      ->set('custom_directory', $form_state->getValue(['campaignmonitor_custom', 'custom_directory']))
      ->set('node_types', $form_state->getValue(['campaignmonitor_types', 'node_types']))
      ->set('css_library', $form_state->getValue('css_library'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
