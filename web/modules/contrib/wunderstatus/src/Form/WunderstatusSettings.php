<?php

namespace Drupal\wunderstatus\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WunderstatusSettings.
 *
 * @package Drupal\wunderstatus\Form
 */
class WunderstatusSettings extends ConfigFormBase {
  
  /** @var StateInterface */
  protected $state;
  
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state) {
    parent::__construct($config_factory);
    $this->state = $state;
  }
  
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['wunderstatus.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wunderstatus_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['authentication'] = [
      '#type' => 'fieldset',
      '#title' => t('Authentication'),
    ];
    
    $form['authentication']['wunderstatus_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wunderstatus authentication key'),
      '#maxlength' => 128,
      '#size' => 65,
      '#required' => TRUE,
      '#default_value' => $this->state->get('wunderstatus_key'),
    ];

    $form['manager_site'] = [
      '#type' => 'fieldset',
      '#title' => t('Wunderstatus manager'),
    ];

    $form['manager_site']['wunderstatus_manager_endpoint_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#maxlength' => 128,
      '#size' => 65,
      '#required' => TRUE,
      '#default_value' => $this->state->get('wunderstatus_manager_endpoint_url'),
    ];

    $form['manager_site']['wunderstatus_auth_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Auth username'),
      '#maxlength' => 128,
      '#size' => 65,
      '#default_value' => $this->state->get('wunderstatus_auth_username'),
    ];

    $form['manager_site']['wunderstatus_auth_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Auth password'),
      '#maxlength' => 128,
      '#size' => 65,
      '#default_value' => $this->state->get('wunderstatus_auth_password'),
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
    $this->state->set('wunderstatus_key', $form_state->getValue('wunderstatus_key'));
    $this->state->set('wunderstatus_manager_endpoint_url', $form_state->getValue('wunderstatus_manager_endpoint_url'));
    $this->state->set('wunderstatus_auth_username', $form_state->getValue('wunderstatus_auth_username'));
    $this->state->set('wunderstatus_auth_password', $form_state->getValue('wunderstatus_auth_password'));
  }
}
