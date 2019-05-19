<?php

/**
 * @file
 * Contains \Drupal\notifier_scc\Form\AdminForm.
 */

namespace Drupal\notifier_scc\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Config\ConfigFactoryInterface;
use Egulias\EmailValidator\EmailValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AdminForm extends ConfigFormBase {

  var $config;
  var $email_validator;

  /**
   * Constructs a new form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EmailValidator $email_validator) {
    parent::__construct($config_factory);

    $this->config = $config_factory->getEditable('notifier_scc.settings');
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notifier_scc_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['notifier_scc.settings'];
  }

  /**
   * @inheritdoc
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $key = 'notification_email';

    $default_value = $this->config->get($key);

    $description = $this->t('Please specify the email address to be notified when a currency conversion fails.');

    $form[$key] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification Email'),
      '#default_value' => $default_value,
      '#description' => $description,
    ];

    $key = 'notification_email_time';

    $default_value = $this->config->get($key);

    $description = $this->t('Please specify the time interval in seconds between email notifications.');

    $form[$key] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification Email Interval'),
      '#default_value' => $default_value,
      '#description' => $description,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * @inheritdoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $key = 'notification_email';

    $notification_email = $form_state->getValue($key);

    $result = $this->emailValidator->isValid($notification_email);

    if (!$result) {
      $form_state->setErrorByName($key, t('Please specify a valid notification email address'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach (Element::children($form) as $variable) {
      $this->config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }

    $this->config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}
