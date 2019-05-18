<?php

namespace Drupal\register_display\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\register_display\RegisterDisplayServices;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\register_display\Form
 */
class SettingsForm extends ConfigFormBase {
  protected $services;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, RegisterDisplayServices $services) {
    parent::__construct($config_factory);
    $this->services = $services;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('register_display.services')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'register_display_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'register_display.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('register_display.settings');
    $disabledSettings = FALSE;
    // Get registration pages.
    $registrationPages = $this->services->getRegistrationPagesOptions();

    if (!$registrationPages) {
      $disabledSettings = TRUE;
    }

    $form['redirectSettings'] = [
      '#type' => 'details',
      '#title' => $this->t('Redirect settings'),
      '#description' => $disabledSettings ?
      $this->t('This option available only if you already have at least one register page.') :
      $this->t('Redirect user/register to one of custom registration pages.'),
      '#open' => TRUE,
    ];

    $form['redirectSettings']['isRedirect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Redirect original register page?'),
      '#disabled' => $disabledSettings,
      '#default_value' => $config->get('isRedirect'),
    ];

    if (!$disabledSettings) {
      $form['redirectSettings']['redirectTarget'] = [
        '#type' => 'select',
        '#title' => $this->t('Select page'),
        '#options' => $registrationPages,
        '#required' => TRUE,
        '#default_value' => $config->get('redirectTarget'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->configFactory->getEditable('register_display.settings')
      ->set('isRedirect', $form_state->getValue('isRedirect'))
      ->set('redirectTarget', $form_state->getValue('redirectTarget'))
      ->save();
    $this->services->clearRouteCache();
  }

}
