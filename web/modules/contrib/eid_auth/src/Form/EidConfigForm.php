<?php

namespace Drupal\eid_auth\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EidConfigForm.
 *
 * @package Drupal\eid_auth\Form
 */
class EidConfigForm extends ConfigFormBase {

  /**
   * Path validator service.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   Path validator service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator) {
    parent::__construct($config_factory);

    $this->pathValidator = $path_validator;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'eid_auth.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eid_auth_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('eid_auth.settings');

    $form['enabled_auth_methods'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled authentication methods'),
      '#options' => [
        'id_card' => $this->t('ID-Card'),
        'mobile_id' => $this->t('Mobile-ID'),
        'smart_id' => $this->t('Smart-ID'),
      ],
      '#default_value' => $config->get('enabled_auth_methods') ?: [],
    ];

    $form['mobile_id'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mobile-ID settings'),
    ];

    $form['mobile_id']['wsdl_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('WSDL url'),
      '#default_value' => $config->get('mobile_id_wsdl'),
    ];

    $form['mobile_id']['service_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service name'),
      '#default_value' => $config->get('mobile_id_service_name'),
    ];

    $form['mobile_id']['display_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display message'),
      '#default_value' => $config->get('mobile_id_display_message'),
    ];

    $form['smart_id'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Smart-ID settings'),
    ];

    $form['smart_id']['relying_party_uuid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Relying Party UUID'),
      '#default_value' => $config->get('smart_id_relying_party_uuid'),
    ];

    $form['smart_id']['relying_party_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Relying Party Name'),
      '#default_value' => $config->get('smart_id_relying_party_name'),
    ];

    $form['smart_id']['host_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host URL'),
      '#default_value' => $config->get('smart_id_host_url'),
    ];

    $form['smart_id']['resource_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Resource location'),
      '#description' => $this->t('Absolute path to certificates folder. Leave empty for default library path.'),
      '#default_value' => $config->get('smart_id_resource_location'),
    ];

    $form['login_redirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect path'),
      '#description' => $this->t('Path to redirect after successful login.'),
      '#default_value' => $config->get('login_redirect'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $redirect_path = $form_state->getValue('login_redirect');

    if (!empty($redirect_path)) {
      $url = $this->pathValidator->getUrlIfValidWithoutAccessCheck($redirect_path);

      if (!$url) {
        $form_state->setErrorByName('login_redirect', 'Redirect path must exist!');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('eid_auth.settings')
      // Mobile-ID configuration save.
      ->set('mobile_id_wsdl', trim($form_state->getValue('wsdl_url')))
      ->set('mobile_id_service_name', $form_state->getValue('service_name'))
      ->set('mobile_id_display_message', $form_state->getValue('display_message'))
      // SmartID configuration save.
      ->set('smart_id_relying_party_uuid', $form_state->getValue('relying_party_uuid'))
      ->set('smart_id_relying_party_name', $form_state->getValue('relying_party_name'))
      ->set('smart_id_host_url', $form_state->getValue('host_url'))
      ->set('smart_id_resource_location', $form_state->getValue('resource_location'))
      // Enabled auth methods save.
      ->set('enabled_auth_methods', $form_state->getValue('enabled_auth_methods'))
      // Redirect save.
      ->set('login_redirect', $form_state->getValue('login_redirect'))
      ->save();
  }

}
