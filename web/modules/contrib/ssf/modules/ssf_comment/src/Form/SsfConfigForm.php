<?php

namespace Drupal\ssf_comment\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Email;
use Egulias\EmailValidator\EmailValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SsfConfigForm.
 *
 * @package Drupal\ssf_comment\Form
 */
class SsfConfigForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * E-mail validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * SsfConfigForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory interface.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler interface.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   E-mail validator.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
    EmailValidator $email_validator
  ) {
    parent::__construct($config_factory);

    $this->moduleHandler = $module_handler;
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ssf_comment.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ssf_comment_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ssf_comment.settings');

    // Fieldset for check.
    $form['check'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Thresholds'),
      '#description' => $this->t('Set thresholds that will be used to designate a comment to be spam or ham.<br>Values closer to 0 will more likely be ham, while values closer to 100 will more likely be spam.<br>The values in the gap between the thresholds will be designated as unknown.<br>(Internally values between 0 and 1 are used).'),
      '#collapsible' => FALSE,
    ];
    $form['check']['ssf_comment_ham_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Ham threshold'),
      '#description' => $this->t('Classification of comments with a value below this threshold will be designated ham.'),
      '#default_value' => $config->get('ssf_comment_ham_threshold'),
      '#min' => 1,
      '#max' => 99,
      '#step' => 1,
    ];
    $form['check']['ssf_comment_spam_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Spam threshold'),
      '#description' => $this->t('Classification of comments with a value above this threshold will be designated spam.'),
      '#default_value' => $config->get('ssf_comment_spam_threshold'),
      '#min' => 1,
      '#max' => 99,
      '#step' => 1,
    ];

    // Fieldset for notification.
    $form['notify'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('E-mail notification'),
      '#description' => $this->t('Send an e-mail notification when new comments are waiting for approval.'),
      '#collapsible' => FALSE,
    ];
    $form['notify']['ssf_comment_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify when a comment is waiting for approval.'),
      '#description' => $this->t('Whenever a comment is waiting for approval (including ham).'),
      '#default_value' => $config->get('ssf_comment_notify'),
    ];
    $form['notify']['ssf_comment_mail_addresses'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification e-mail addresses.'),
      '#description' => $this->t('E-mail addresses to use for notification (comma-seperated).<br>E-mails are sent in the HTML format. You will need a module such as <a href="https://www.drupal.org/project/swiftmailer">Swiftmailer</a> to send HTML e-mails.'),
      '#default_value' => $config->get('ssf_comment_mail_addresses'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ssf_comment.settings');
    $config
      ->set('ssf_comment_ham_threshold', $form_state->getValue('ssf_comment_ham_threshold'))
      ->set('ssf_comment_spam_threshold', $form_state->getValue('ssf_comment_spam_threshold'))
      ->set('ssf_comment_notify', $form_state->getValue('ssf_comment_notify'))
      ->set('ssf_comment_mail_addresses', $form_state->getValue('ssf_comment_mail_addresses'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->getValue('ssf_comment_ham_threshold') > $form_state->getValue('ssf_comment_spam_threshold')) {
      $message = $this->t('The threshold for ham cannot be higher than the threshold for spam.');
      $form_state->setErrorByName('ssf_comment_ham_threshold', $message);
      $message = $this->t('The threshold for spam cannot be lower than the threshold for ham.');
      $form_state->setErrorByName('ssf_comment_spam_threshold', $message);
    }

    if ($form_state->getValue('ssf_comment_mail_addresses')) {
      $addresses = explode(',', $form_state->getValue('ssf_comment_mail_addresses'));
      $validator = \Drupal::service('email.validator');
      foreach ($addresses as $address) {
        if (!$this->emailValidator->isValid($address)) {
          $message = $this->t('"@mail" is not a valid e-mail address.', ['@mail' => $address]);
          $form_state->setErrorByName('ssf_comment_mail_addresses', $message);
          break;
        }
      }
    }
  }

}
