<?php

/**
 * @file
 * Contains \Drupal\suretax\Form\SettingsForm.
 */

namespace Drupal\suretax\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Defines a form that configures devel settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'suretax_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'suretax.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $suretax_config = $this->config('suretax.settings');
    $form = array();
    $form['suretax_checkout_name'] = array(
      '#title' => t('SureTax Name'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => t('Enter SureTax name that you would like to display in Checkout page.'),
      '#default_value' => $suretax_config->get('suretax_checkout_name'),
    );
    $specifications = array(
      'general' => t('General Sales , Communications or Utilities Transaction'),
    );
    $form['suretax_type'] = array(
      '#title' => t('SureTax API Specification'),
      '#type' => 'radios',
      '#options' => $specifications,
      '#default_value' => $suretax_config->get('suretax_type'),
      '#required' => TRUE,
    );
    $mode = $suretax_config->get('suretax_mode');
    $form_state_values = $form_state->getValues();
    if (isset($form_state_values) && ($form_state->getValues('suretax_mode'))) {
      $values = $form_state->getValues();
      $mode = $values['suretax_mode'];
    }
    $form['suretax_credentials'] = array(
      '#type' => 'details',
      '#title' => t('Suretax Credentials'),
      '#open' => TRUE,
    );
    $mode_options = array('Development' => 'Development', 'Live' => 'Live');
    $form['suretax_credentials']['suretax_mode'] = array(
      '#title' => t('Select Mode'),
      '#type' => 'select',
      '#options' => $mode_options,
      '#required' => TRUE,
      '#description' => 'Select Live mode only in Production Environment',
      '#default_value' => $suretax_config->get('suretax_mode'),
      '#ajax' => [
        'callback' => array($this, 'suretaxmodeAjax'),
        'event' => 'change',
        'progress' => array(
          'type' => 'throbber',
          'message' => t('Changing Suretax Mode...'),
        ),
      ],
    );
    $form['suretax_credentials']['data'] = array(
      '#type' => 'container',
      '#prefix' => '<div id ="suretax-credentials">',
      '#suffix' => '</div>',
    );
    $form['suretax_credentials']['data']['suretax_client_id_' . $mode] = array(
      '#title' => t('Client ID'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => t('Enter Client Id for @mode site', array('@mode' => $mode)),
      '#default_value' => $suretax_config->get('suretax_client_id_' . $mode),
      '#size' => 10,
      '#maxlength' => 10,
    );
    $form['suretax_credentials']['data']['suretax_client_id_' . $mode] = array(
      '#title' => t('Client ID'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => t('Enter Client Id for @mode site', array('@mode' => $mode)),
      '#default_value' => $suretax_config->get('suretax_client_id_' . $mode),
      '#size' => 10,
      '#maxlength' => 10,
    );
    $form['suretax_credentials']['data']['suretax_validation_key_' . $mode] = array(
      '#title' => t('Validation Key'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => t('Enter License Key for @mode site', array('@mode' => $mode)),
      '#default_value' => $suretax_config->get('suretax_validation_key_' . $mode),
      '#size' => 36,
      '#maxlength' => 36,
    );
    $form['suretax_credentials']['data']['suretax_business_unit_code_' . $mode] = array(
      '#title' => t('Business Unit Code'),
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#description' => t('Enter Company Code for @mode site', array('@mode' => $mode)),
      '#default_value' => '000000697',
      '#size' => 20,
      '#maxlength' => 20,
    );
    $form['suretax_credentials']['data']['suretax_api_' . $mode] = array(
      '#title' => t('SureTax POST API'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => t('Enter Post API Details for @mode site', array('@mode' => $mode)),
      '#default_value' => $suretax_config->get('suretax_api_' . $mode),
    );
    $form['suretax_credentials']['data']['suretax_cancel_api_' . $mode] = array(
      '#title' => t('SureTax Cancel API'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => t('Enter Cancel API Details for @mode site', array('@mode' => $mode)),
      '#default_value' => $suretax_config->get('suretax_cancel_api_' . $mode),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $mode = $values['suretax_mode'];
    // Set Suretax config values.
    $this->config('suretax.settings')
        ->set('suretax_checkout_name', $values['suretax_checkout_name'])
        ->set('suretax_type', $values['suretax_type'])
        ->set('suretax_mode', $values['suretax_mode'])
        ->set('error_handlers', $values['error_handlers'])
        ->set('suretax_client_id_' . $mode, $values['suretax_client_id_' . $mode])
        ->set('suretax_validation_key_' . $mode, $values['suretax_validation_key_' . $mode])
        ->set('suretax_api_' . $mode, $values['suretax_api_' . $mode])
        ->set('suretax_cancel_api_' . $mode, $values['suretax_cancel_api_' . $mode])
        ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * 
   * @param array $form
   * @param FormStateInterface $form_state
   * @return AjaxResponse
   * Ajax callback for SureTax Mode.
   */
  public function suretaxmodeAjax(array &$form, FormStateInterface $form_state) {
    $valid = $this->validatemode($form, $form_state);
    $response = new AjaxResponse();
    if ($valid) {
      $css = ['border' => '1px solid green'];
      $message = $valid;
    }
    $response->addCommand(new CssCommand('#edit-suretax-mode', $css));
    $response->addCommand(new HtmlCommand('#suretax-credentials', $message));
    return $response;
  }

  protected function validatemode(array &$form, FormStateInterface $form_state) {
    return $form['suretax_credentials']['data'];
  }

}
