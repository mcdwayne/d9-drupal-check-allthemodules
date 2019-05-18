<?php

namespace Drupal\purest_recaptcha\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Class ConfigForm.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new RecaptchaConfigForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state) {
    parent::__construct($config_factory);
    $this->configFactory = $config_factory;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
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
    return [
      'purest_recaptcha.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recaptcha_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['recaptcha'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Recaptcha Keys'),
    ];

    $form['recaptcha']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Id'),
      '#description' => $this->t('The client ID for the Recaptcha service'),
      '#maxlength' => 256,
      '#size' => 64,
      '#default_value' => $this->state->get('purest_recaptcha.client_id'),
    ];

    $form['recaptcha']['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Key'),
      '#description' => $this->t('The secret key for the Recaptcha service'),
      '#maxlength' => 256,
      '#size' => 64,
      '#default_value' => $this->state->get('purest_recaptcha.secret_key'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->state->set('purest_recaptcha.client_id', $form_state->getValue('client_id'));
    $this->state->set('purest_recaptcha.secret_key', $form_state->getValue('secret_key'));
  }

}
