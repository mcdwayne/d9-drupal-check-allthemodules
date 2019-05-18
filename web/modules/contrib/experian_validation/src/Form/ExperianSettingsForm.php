<?php

namespace Drupal\experian_validation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ExperianSettingsForm.
 *
 * @package Drupal\experian_validation\Form
 */
class ExperianSettingsForm extends ConfigFormBase {
  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs LinkGenerator object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State Service Object.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'experian.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'experian_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $state = $this->state;
    $country = [
      '+1' => 'United States',
      '+6' => 'Australia',
      '+68' => 'Canada',
      '+66' => 'France',
      '+56' => 'Ireland',
      '+60' => 'Singapore',
      '+61' => 'United Kingdom',
    ];
    $form['experianPhone'] = [
      '#type' => 'details',
      '#title' => $this->t('Experian Phone number API settings'),
      '#open' => TRUE,
    ];
    $form['experianPhone']['expPhoneCountry'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#options' => $country,
      '#default_value' => !empty($state->get('expPhoneCountry')) ? $state->get('expPhoneCountry') : '',
      '#description' => $this->t('Enter the experian endpoint for phone number.'),
    ];
    $form['experianPhone']['expPhoneEndPoint'] = [
      '#type' => 'url',
      '#title' => $this->t('API endPoint'),
      '#size' => 60,
      '#maxlength' => 255,
      '#default_value' => !empty($state->get('expPhoneEndPoint')) ? $state->get('expPhoneEndPoint') : '',
      '#description' => $this->t('Enter the experian endpoint for phone number.'),
    ];
    $form['experianPhone']['expPhoneToken'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Token'),
      '#size' => 60,
      '#maxlength' => 255,
      '#default_value' => !empty($state->get('expPhoneToken')) ? $state->get('expPhoneToken') : '',
      '#description' => $this->t('Enter the experian API Token form phone number.'),
    ];
    $form['experianEmail'] = [
      '#type' => 'details',
      '#title' => $this->t('Experian email address API settings'),
      '#open' => TRUE,
    ];
    $form['experianEmail']['expEmailEndPoint'] = [
      '#type' => 'url',
      '#title' => $this->t('API endPoint'),
      '#size' => 60,
      '#maxlength' => 255,
      '#default_value' => !empty($state->get('expEmailEndPoint')) ? $state->get('expEmailEndPoint') : '',
      '#description' => $this->t('Enter the experian endpoint for email address.'),
    ];
    $form['experianEmail']['expEmailToken'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Token'),
      '#size' => 60,
      '#maxlength' => 255,
      '#default_value' => !empty($state->get('expEmailToken')) ? $state->get('expEmailToken') : '',
      '#description' => $this->t('Enter the experian API Token form email address.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = [
      'expPhoneCountry' => $form_state->getValue('expPhoneCountry'),
      'expPhoneEndPoint' => $form_state->getValue('expPhoneEndPoint'),
      'expPhoneToken' => $form_state->getValue('expPhoneToken'),
      'expEmailEndPoint' => $form_state->getValue('expEmailEndPoint'),
      'expEmailToken' => $form_state->getValue('expEmailToken'),
    ];
    $this->state->setMultiple($values);
  }

}
