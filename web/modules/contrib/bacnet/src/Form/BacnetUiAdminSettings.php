<?php

/**
 * @file
 * Contains \Drupal\bacnet\Form\BacnetUiAdminSettings.
 */

namespace Drupal\bacnet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class BacnetUiAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bacnet_ui_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bacnet.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bacnet.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // @FIXME
// Could not extract the default value because it is either indeterminate, or
// not scalar. You'll need to provide a default value in
// config/install/bacnet.settings.yml and config/schema/bacnet.schema.yml.
    $form['bacnet_product_version'] = [
      '#title' => t('Select OpenEnergy Version'),
      '#type' => 'select',
      '#options' => [
        BACNET_COMMUNITY => t('OpenEnergy Community')
        ],
      '#default_value' => \Drupal::config('bacnet.settings')->get('bacnet_product_version'),
      '#ajax' => [
        'callback' => 'bacnet_ui_update_form_options',
        'wrapper' => 'bacnet_options',
      ],
    ];

    $form['options'] = [
      '#type' => 'container',
      '#prefix' => '<div id="bacnet_options">',
      '#suffix' => '</div>',
      '#tree' => FALSE,
    ];

    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/bacnet.settings.yml and config/schema/bacnet.schema.yml.
    $version = \Drupal::config('bacnet.settings')->get('bacnet_product_version');
    if (!$form_state->getValue(['bacnet_product_version']) && $form_state->getValue(['bacnet_product_version'])) {
      $version = $form_state->getValue(['bacnet_product_version']);
    }

    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/bacnet.settings.yml and config/schema/bacnet.schema.yml.
    $mode = \Drupal::config('bacnet.settings')->get('bacnet_mode');
    if (!$form_state->getValue(['bacnet_mode']) && $form_state->getValue(['bacnet_mode'])) {
      $mode = $form_state->getValue(['bacnet_mode']);
    }

    if ($version == BACNET_COMMUNITY) {
      $form['options'] = [
        '#type' => 'fieldset',
        '#title' => t('BACnet Block Configuration'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#tree' => FALSE,
      ];

      $form['options']['bacnet'] = [
        '#type' => 'container',
        '#prefix' => '<div id="bacnet_mode">',
        '#suffix' => '</div>',
        '#tree' => FALSE,
      ];

      $bacnet_modes = [
        BACNET_MODE_BWS1 => t('BWS1 SOAP'),
        BACNET_MODE_BWS2 => t('BWS2 REST'),
      ];

      // Select the BACnet mode for the blocks configuration.
      $form['options']['bacnet']['bacnet_mode'] = [
        '#title' => t('BACnet Mode'),
        '#description' => t('Select Mode applicable to this block'),
        '#type' => 'select',
        '#multiple' => FALSE,
        '#options' => $bacnet_modes,
        '#default_value' => $mode,
        '#ajax' => [
          'callback' => 'bacnet_ui_update_form_options_mode',
          'wrapper' => 'bacnet_mode',
        ],
      ];

      $form['options']['bacnet']['bacnet_bws1_server'] = [
        '#type' => 'textfield',
        '#title' => t('BACnet Server Address'),
        '#access' => $mode == BACNET_MODE_BWS1 ? TRUE : FALSE,
        '#default_value' => \Drupal::config('bacnet.settings')->get('bacnet_bws1_server'),
        '#required' => TRUE,
        '#description' => t('Include (http:// or https://) + (domain OR IP address) + (:port) <br /> Do not include the WSDL path /_common/webservices/Eval?WSDL<br />Examples: <ul><li>Standard Port &mdash; http://192.0.43.10 or http://www.example.com</li><li>Non-standard Port &mdash; https://www.example.com:8080</li></ul>'),
      ];

      $form['options']['bacnet']['bacnet_bws1_login_name'] = [
        '#type' => 'textfield',
        '#title' => t('B-AWS Login Name'),
        '#access' => $mode == BACNET_MODE_BWS1 ? TRUE : FALSE,
        '#default_value' => \Drupal::config('bacnet.settings')->get('bacnet_bws1_login_name'),
        '#required' => TRUE,
        '#description' => t('Must have:<ul><li>Remote Data Access</li><li>Access Geographic Locations or Network Locations, as needed</li><li>Access Network items, as needed</li><li>Any privileges needed for the specific task at least Remote Data Access</li></ul>'),
      ];

      $form['options']['bacnet']['bacnet_bws1_password'] = [
        '#type' => 'password',
        '#title' => t('B-AWS Password'),
        '#access' => $mode == BACNET_MODE_BWS1 ? TRUE : FALSE,
        '#required' => TRUE,
        '#description' => t('Must have:<ul><li>Remote Data Access</li><li>Access Geographic Locations or Network Locations, as needed</li><li>Access Network items, as needed</li><li>Any privileges needed for the specific task at least Remote Data Access</li></ul>'),
      ];

      $form['options']['bacnet']['bacnet_bws1_gql_expression'] = [
        '#type' => 'textfield',
        '#title' => t('The Test Point GQL Expression (the path to the point)'),
        '#access' => $mode == BACNET_MODE_BWS1 ? TRUE : FALSE,
        '#default_value' => \Drupal::config('bacnet.settings')->get('bacnet_bws1_gql_expression'),
        '#optional' => TRUE,
        '#description' => t('Expression only needs to refer to the microblock; present_value is assumed.<br />For example, #g01_electric_meter/inst_demand'),
      ];
      $form['options']['bacnet']['bacnet_bws2_vendor_id'] = [
        '#type' => 'textfield',
        '#title' => t('BACnet Vendor id'),
        '#access' => $mode == BACNET_MODE_BWS2 ? TRUE : FALSE,
        '#default_value' => \Drupal::config('bacnet.settings')->get('bacnet_bws2_vendor_id'),
        '#optional' => TRUE,
        '#description' => t('Vendor ID for testing integration with BWS2 REST'),
      ];
    }
    return parent::buildForm($form, $form_state);
  }

}
