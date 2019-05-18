<?php

/**
 * @file
 * Contains \Drupal\email_verify\Form\EmailVerifyAdminForm.
 */

namespace Drupal\email_verify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\email_verify\EmailVerifyManager;
use Drupal\email_verify\EmailVerifyManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for administering Email Verify configuration.
 */
class EmailVerifyAdminForm extends ConfigFormBase {

  /**
   * The email verify manager.
   *
   * @var \Drupal\email_verify\EmailVerifyManagerInterface
   */
  protected $emailVerifyManager;

  /**
   * Constructs a new EmailVerifyAdminForm.
   *
   * @param \Drupal\email_verify\EmailVerifyManagerInterface $email_verify_manager
   *   The email verify manager.
   */
  public function __construct(EmailVerifyManagerInterface $email_verify_manager) {
    $this->emailVerifyManager = $email_verify_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('email_verify.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_verify_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['email_verify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('email_verify.settings');

    $form['active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Email Verify to verify email adresses'),
      '#default_value' => $config->get('active'),
      '#description' => $this->t('When activated, Email Verify will check full email addresses for validity. When unchecked, Email Verify will just check hosts.'),
    ];

    $form['test_options'] = array(
      '#type' => 'fieldset',
      '#title' => t('Testing options'),
      '#collapsible' => TRUE,
      '#description' => t("If the test fails when checking whether this module will work on your system or not, you can try changing the options below to see if they will work better for you."),
    );
    $form['test_options']['host_name'] = array(
      '#type' => 'textfield',
      '#title' => t("Host name"),
      '#default_value' => $config->get('host_name'),
      '#description' => t('The name of the host to test with. The default is "drupal.org".'),
    );
    $form['test_options']['timeout'] = array(
      '#type' => 'textfield',
      '#title' => t("Timeout"),
      '#default_value' => $config->get('timeout'),
      '#description' => t('The connection timeout, in seconds. The default is "15".'),
    );

    $form['verify_methods'] = array(
      '#type' => 'fieldset',
      '#title' => t('Methods to use'),
      '#collapsible' => TRUE,
      '#description' => t("Check the boxes for the various methods to use when verifying email addresses. If you find you're getting lots of false positives nad/or false negatives, try changing which options are enabled."),
    );
    $form['verify_methods']['checkdnsrr'] = array(
      '#type' => 'checkbox',
      '#title' => t("Check for any DNS records"),
      '#default_value' => $config->get('checkdnsrr'),
      '#description' => t("Use PHP's checkdnsrr() function to see if there are any DNS records associated with the email address' domain name."),
    );
    $form['verify_methods']['gethostbyname'] = array(
      '#type' => 'checkbox',
      '#title' => t("Check for a valid IPv4 address"),
      '#default_value' => $config->get('gethostbyname'),
      '#description' => t("Use PHP's gethostbyname() function to see if a valid IPv4 address is associated with the email address' domain name."),
    );
    $form['verify_methods']['add_dot'] = array(
      '#type' => 'checkbox',
      '#title' => t("Add a dot to the domain"),
      '#default_value' => $config->get('add_dot'),
      '#description' => t("For hosts that add their own domain to the end of the domain in the email address, this adds an additional '.' to the end of the email address domain, so that the check will not fail at the wrong time."),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $config = $this->config('email_verify.settings');

    if ($config->get('active') !== 1 &&
      $form_state->getValue('active')) {
      $this->emailVerifyManager->checkHost($config->get('host_name'));
      if ($this->emailVerifyManager->getErrors()) {
        $form_state->setErrorByName('active', $this->t("Email Verify will test email domains but not mailboxes because port 25 is closed on your host's firewall"));
        \Drupal::logger('email_verify')->warning('Email Verify cannot test mailboxes because port 25 is closed.');
      }
      \Drupal::logger('email_verify')->notice('The Email Verify module was activated.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('email_verify.settings')
      ->set('active', $form_state->getValue('active'))
      ->set('user_registration', $form_state->getValue('user_registration'))
      ->set('user_profile', $form_state->getValue('user_profile'))
      ->set('site_contact', $form_state->getValue('site_contact'))
      ->set('personal_contact', $form_state->getValue('personal_contact'))
      ->set('checkdnsrr', $form_state->getValue('checkdnsrr'))
      ->set('gethostbyname', $form_state->getValue('gethostbyname'))
      ->set('add_dot', $form_state->getValue('add_dot'))
      ->set('host_name', $form_state->getValue('host_name'))
      ->set('timeout', $form_state->getValue('timeout'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
