<?php

namespace Drupal\email_confirmer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Email confirmer settings form.
 */
class EmailConfirmerSettingsForm extends ConfigFormBase {

  /**
   * One hour seconds.
   */
  const SECONDS_PER_HOUR = 3600;

  /**
   * One day seconds.
   */
  const SECONDS_PER_DAY = 86400;

  /**
   * One week seconds.
   */
  const SECONDS_PER_WEEK = 604800;

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(PathValidatorInterface $path_validator, ModuleHandlerInterface $module_handler) {
    $this->pathValidator = $path_validator;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.validator'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'email_confirmer_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'email_confirmer.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\Core\Config\ImmutableConfig $config */
    $config = $this->config('email_confirmer.settings');

    // Confirmation process expiration.
    $form['hash_expiration'] = [
      '#type' => 'select',
      '#title' => $this->t('Response time limit'),
      '#description' => $this->t('Maximum time to attend the confirmation request from its creation.'),
      '#options' => [
        '1' => $this->t('1 hour'),
      ],
      '#default_value' => $config->get('hash_expiration') ? round($config->get('hash_expiration') / self::SECONDS_PER_HOUR) : 24,
      '#required' => TRUE,
    ];
    for ($i = 2; $i <= 48; $i++) {
      $form['hash_expiration']['#options'][$i] = $this->t('@count hours', ['@count' => $i]);
      if ($i > 11) {
        // Two hours step from 12h.
        $i++;
        if ($i > 23) {
          // 4 hours step from 24h.
          $i += 2;
        }
      }
    }

    // Confirmations lifetime.
    $form['confirmation_lifetime'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum confirmation lifetime'),
      '#description' => $this->t('Confirmations older than this value will be purged from database.'),
      '#options' => [
        '1' => $this->t('1 week'),
      ],
      '#default_value' => $config->get('confirmation_lifetime') ? round($config->get('confirmation_lifetime') / self::SECONDS_PER_WEEK) : 2,
      '#required' => TRUE,
    ];
    for ($i = 2; $i <= 7; $i++) {
      $form['confirmation_lifetime']['#options'][$i] = $this->t('@count weeks', ['@count' => $i]);
    }
    for ($i = 2; $i <= 11; $i++) {
      $form['confirmation_lifetime']['#options'][round($i * 4.33)] = $this->t('@count months', ['@count' => $i]);
    }
    $form['confirmation_lifetime']['#options'][52] = $this->t('1 year');
    $form['confirmation_lifetime']['#options'][0] = $this->t('- Do not purge -');

    // Delay before re-send request.
    $form['resendrequest_delay'] = [
      '#type' => 'select',
      '#title' => $this->t('Delay before re-send request'),
      '#description' => $this->t('Time lapse between same confirmation request sendings.'),
      '#options' => [],
      '#default_value' => $config->get('resendrequest_delay') ? round($config->get('resendrequest_delay') / 60) : 15,
      '#required' => TRUE,
    ];
    for ($i = 5; $i <= 60; $i += 5) {
      $form['resendrequest_delay']['#options'][$i] = $this->t('@count minutes', ['@count' => $i]);
    }

    // Same IP response restriction.
    $form['restrict_same_ip'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Accept email confirmation responses only from the same IP address that was requested'),
      '#default_value' => $config->get('restrict_same_ip') || FALSE,
    ];

    // Confirmation request email subject & body.
    $form['confirmationrequest_mail'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmation email'),
    ];

    $form['confirmationrequest_mail']['confirmationrequest_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $config->get('confirmation_request.subject'),
      '#maxlength' => 180,
    ];

    $form['confirmationrequest_mail']['confirmationrequest_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body text'),
      '#default_value' => $config->get('confirmation_request.body'),
      '#rows' => 6,
    ];

    if ($this->moduleHandler->moduleExists('token')) {
      $form['confirmationrequest_mail']['token_help'] = [
        '#title' => $this->t('Replacement patterns'),
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $form['confirmationrequest_mail']['token_help']['browser'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['email-confirmer'],
      ];
    }

    // Confirmation response form messages.
    $form['confirmationresponse'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmation response messages'),
    ];

    foreach (['pending', 'expired', 'cancelled', 'confirmed'] as $status) {
      $form['confirmationresponse']['confirmationresponse_' . $status] = [
        '#type' => 'textfield',
        '#title' => $this->t('On @status confirmations', ['@status' => $status]),
        '#description' => $this->t('Displayed on replying @status confirmations.', ['@status' => $status]),
        '#default_value' => $config->get('confirmation_response.questions.' . $status),
        '#maxlength' => 255,
      ];
    }

    // Confirmation response URLs.
    $form['response_url'] = [
      '#type' => 'details',
      '#title' => $this->t('Confirmation pages'),
    ];

    $form['response_url']['confirmationresponse_url_confirm'] = [
      '#type' => 'textfield',
      '#title' => $this->t('On confirmation URL'),
      '#description' => $this->t('Default Drupal path or URL to go after an email is confirmed. Default to the front page.'),
      '#default_value' => $config->get('confirmation_response.url.confirm'),
    ];

    $form['response_url']['confirmationresponse_url_cancel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('On cancellation URL'),
      '#description' => $this->t('Default Drupal path or URL to go after an email is cancelled. Default to the front page.'),
      '#default_value' => $config->get('confirmation_response.url.cancel'),
    ];

    $form['response_url']['confirmationresponse_url_error'] = [
      '#type' => 'textfield',
      '#title' => $this->t('On error URL'),
      '#description' => $this->t('Default Drupal path or URL to go where a confirmation error takes pace. Default to the front page.'),
      '#default_value' => $config->get('confirmation_response.url.error'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('email_confirmer.settings');
    foreach (['pending', 'expired', 'cancelled', 'confirmed'] as $status) {
      $config->set('confirmation_response.questions.' . $status, $form_state->getValue('confirmationresponse_' . $status));
    }

    $config->set('hash_expiration', intval($form_state->getValue('hash_expiration')) * self::SECONDS_PER_HOUR)
      ->set('confirmation_lifetime', intval($form_state->getValue('confirmation_lifetime')) * self::SECONDS_PER_WEEK)
      ->set('restrict_same_ip', $form_state->getValue('restrict_same_ip') || FALSE)
      ->set('resendrequest_delay', intval($form_state->getValue('resendrequest_delay')) * 60)
      ->set('confirmation_request.subject', $form_state->getValue('confirmationrequest_subject'))
      ->set('confirmation_request.body', $form_state->getValue('confirmationrequest_body'))
      ->set('confirmation_response.url.confirm', $form_state->getValue('confirmationresponse_url_confirm'))
      ->set('confirmation_response.url.cancel', $form_state->getValue('confirmationresponse_url_cancel'))
      ->set('confirmation_response.url.error', $form_state->getValue('confirmationresponse_url_error'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Hash expiration validation.
    $hash_expiration = intval($form_state->getValue('hash_expiration'));
    if ($hash_expiration < 1) {
      $form_state->setErrorByName('hash_expiration', $this->t('The miminum hash expiration time is @min_value.', ['@min_value' => $this->t('one hour')]));
    }
    elseif ($hash_expiration > 48) {
      $form_state->setErrorByName('hash_expiration', $this->t('The maximun hash expiration time is @max_value.', ['@max_value' => $this->t('@count days', ['@count' => 2])]));
    }

    // Confirmation max lifetime validation.
    $lifetime = intval($form_state->getValue('confirmation_lifetime'));
    if ($lifetime > 52) {
      $form_state->setErrorByName('confirmation_lifetime', $this->t('The maximun confirmation lifetime is @max_value.', ['@max_value' => $this->t('one year')]));
    }

    // Request re-sendings delay validation.
    $resendrequest_delay = intval($form_state->getValue('resendrequest_delay'));
    if ($resendrequest_delay < 5) {
      $form_state->setErrorByName('resendrequest_delay', $this->t('The miminum confirmation request resend delay is @min_value.', ['@min_value' => $this->t('@count minutes', ['@count' => 5])]));
    }
    elseif ($resendrequest_delay > 60) {
      $form_state->setErrorByName('resendrequest_delay', $this->t('The maximun confirmation request resend delay is @max_value.', ['@max_value' => $this->t('one hour')]));
    }

    // Exit URLs validation.
    foreach (['confirm', 'cancel', 'error'] as $page) {
      $path = $form_state->getValue("confirmationresponse_url_$page", '<front>');
      if ($path != '<front>'
        && !$this->pathValidator->isValid($path)) {
        $form_state->setErrorByName("confirmationresponse_url_$page",
          $this->t("The path '%path' is either invalid or you do not have access to it.", ['%path' => $path]));
      }
    }
  }

}
