<?php

namespace Drupal\prod_check\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for production check.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Constructs a \Drupal\ds\Form\EmergencyForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactory $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'prod_check_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // E-mail settings.
    $form['prod_check_general'] = array(
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#description' => $this->t('Settings to allow certain checks to function properly.'),
      '#open' => TRUE,
    );
    $form['prod_check_general']['site_email'] = array(
      '#type' => 'textfield',
      '#title' => t('Mail check'),
      '#default_value' => $this->config('prod_check.settings')->get('site_email'),
      '#size' => 60,
      '#description' => $this->t('Enter (part of) the e-mail address you always <strong>use when developing</strong> a website. This is used in a regular expression in the "Site e-mail", Contact and Webform modules check.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $values = $form_state->getValues();
    $this->config('prod_check.settings')
      ->set('site_email', $values['site_email'])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array(
      'prod_check.settings'
    );
  }

}
