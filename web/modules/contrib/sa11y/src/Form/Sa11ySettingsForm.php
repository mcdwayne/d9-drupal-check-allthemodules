<?php

namespace Drupal\sa11y\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Egulias\EmailValidator\EmailValidator;

/**
 * Configure Sa11y settings for this site.
 */
class Sa11ySettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * Constructs a new Sa11ySettingsForm.
   *
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   */
  public function __construct(EmailValidator $email_validator) {
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sa11y_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sa11y.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sa11y.settings');

    // @TODO: Remove after beta.
    $form['api_server'] = [
      '#title' => $this->t('API Server'),
      '#type' => 'textfield',
      '#description' => $this->t("The Sa11y API Server."),
      '#default_value' => !empty($config->get('api_server')) ? $config->get('api_server') : 'https://www.sa11y.me',
    ];

    $form['api_key'] = [
      '#title' => $this->t('sa11y API Key'),
      '#required' => TRUE,
      '#type' => 'textfield',
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t("Obtain an API Key from signing up at @link", ['@link' => Link::fromTextAndUrl("Sa11y.me", Url::fromUri("https://www.sa11y.me/drupal/beta"))->toString()])
    ];

    // @TODO: Use cat?.
    $form['rules'] = [
      '#title' => $this->t('Rules to use'),
      '#type' => 'checkboxes',
      '#description' => $this->t('Select which rules to apply to your scans. Select none to use all rules.'),
      '#options' => [
        'wcag2a' => $this->t('WCAG 2.0 Level A'),
        'wcag2aa' => $this->t('WCAG 2.0 Level AA'),
        'section508' => $this->t('Section 508'),
        'best-practice' => $this->t('Best Practice'),
        'experimental' => $this->t('Cutting-edge techniques'),
      ],
      '#default_value' => $config->get('rules'),
    ];

    $form['include'] = [
      '#title' => $this->t('Inclusions'),
      '#descriptions' => $this->t('A list of css selectors to include in the check, each on a new line.'),
      '#type' => 'textarea',
      '#default_value' => $config->get('include'),
    ];

    $form['exclude'] = [
      '#title' => $this->t('Exclusions'),
      '#descriptions' => $this->t('A list of css selectors to exclude in the check, each on a new line.'),
      '#type' => 'textarea',
      '#default_value' => $config->get('exclude'),
    ];

    $form['frequency'] = [
      '#type' => 'radios',
      '#title' => $this->t('Check for issues'),
      '#default_value' => $config->get('frequency'),
      '#options' => [
        '1' => $this->t('Daily'),
        '7' => $this->t('Weekly'),
      ],
      '#description' => $this->t('Select how frequently you want to automatically check for accessibility issues.'),
    ];

    $notification_emails = $config->get('emails');
    $form['notify_emails'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email addresses to notify when reports are available'),
      '#rows' => 4,
      '#default_value' => implode("\n", $notification_emails),
      '#description' => $this->t('Whenever your site checks for issues, it can notify a list of users via email. Put each address on a separate line. If blank, no emails will be sent.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->set('emails', []);
    if (!$form_state->isValueEmpty('notify_emails')) {
      $valid = [];
      $invalid = [];
      foreach (explode("\n", trim($form_state->getValue('notify_emails'))) as $email) {
        $email = trim($email);
        if (!empty($email)) {
          if ($this->emailValidator->isValid($email)) {
            $valid[] = $email;
          }
          else {
            $invalid[] = $email;
          }
        }
      }
      if (empty($invalid)) {
        $form_state->set('emails', $valid);
      }
      elseif (count($invalid) == 1) {
        $form_state->setErrorByName('notify_emails', $this->t('%email is not a valid email address.', ['%email' => reset($invalid)]));
      }
      else {
        $form_state->setErrorByName('notify_emails', $this->t('%emails are not valid email addresses.', ['%emails' => implode(', ', $invalid)]));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('sa11y.settings');
    $config
      ->set('api_server', $form_state->getValue('api_server'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('frequency', $form_state->getValue('frequency'))
      ->set('emails', $form_state->get('emails'))
      ->set('rules', $form_state->getValue('rules'))
      ->set('include', $form_state->getValue('include'))
      ->set('exclude', $form_state->getValue('exclude'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
