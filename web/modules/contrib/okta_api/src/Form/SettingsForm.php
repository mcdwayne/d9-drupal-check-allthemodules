<?php

namespace Drupal\okta_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin form for Okta API settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Settings constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'okta_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'okta_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormTitle() {
    return 'Okta API Settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('okta_api.settings');

    $form['okta_api'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('OKTA API Settings'),
    ];

    $form['okta_api']['okta_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Token'),
      '#description' => $this->t('The API token to use.'),
      '#default_value' => $config->get('okta_api_key'),
    ];

    $form['okta_api']['organisation_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your Okta organisation'),
      '#description' => $this->t('The the organisation you have set up in Okta'),
      '#default_value' => $config->get('organisation_url'),
    ];

    $form['okta_api']['okta_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your Okta domain'),
      '#description' => $this->t('The the domain your organisation uses to log into Okta'),
      '#default_value' => $config->get('okta_domain'),
    ];

    $form['okta_api']['default_group_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default group ID'),
      '#description' => $this->t('The default group id to add the user to in Okta'),
      '#default_value' => $config->get('default_group_id'),
    ];

    // Add checkbox to handle okta preview (oktapreview.com) domain.
    $form['okta_api']['preview_domain'] = [
      '#type' => 'checkbox',
      '#title' => 'Use Okta preview domain',
      '#description' => $this->t('If checked, API will use the Okta preview (oktapreview.com) domain.'),
      '#return_value' => TRUE,
      '#default_value' => $config->get('preview_domain'),
    ];

    // Check for devel module.
    $devel_module_present = $this->moduleHandler->moduleExists('devel');

    $form['debug'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('OKTA API Debugging'),
    ];

    // Add debugging options.
    $form['debug']['debug_response'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug OKTA Responses (requires Devel module)'),
      '#description' => $this->t('Show OKTA Responses'),
      '#default_value' => $config->get('debug_response') && $devel_module_present,
      '#disabled' => !$devel_module_present,
    ];

    $form['debug']['debug_exception'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug OKTA Exception (requires Devel module)'),
      '#description' => $this->t('Show OKTA Exception'),
      '#default_value' => $config->get('debug_exception') && $devel_module_present,
      '#disabled' => !$devel_module_present,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('okta_api.settings')
      ->set('okta_api_key', $form_state->getValue('okta_api_key'))
      ->set('default_group_id', $form_state->getValue('default_group_id'))
      ->set('organisation_url', $form_state->getValue('organisation_url'))
      ->set('okta_domain', $form_state->getValue('okta_domain'))
      ->set('preview_domain', $form_state->getValue('preview_domain'))
      ->set('debug_response', $form_state->getValue('debug_response'))
      ->set('debug_exception', $form_state->getValue('debug_exception'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
