<?php

namespace Drupal\okta_import\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBase;
use Drupal\okta_api\Service\Users as OktaApiUsers;
use Psr\Log\LoggerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\okta_import\Event\ValidateUserImportEvent;
use Drupal\okta_import\Event\PreUserImportEvent;
use Drupal\okta_import\Event\PostUserImportEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\okta\Service\User as OktaUser;

/**
 * Implements the Okta Import form controller.
 *
 * @package Drupal\okta_import\Form
 */
class Import extends FormBase {

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Okta User Service.
   *
   * @var \Drupal\okta\Service\User
   */
  protected $oktaUser;

  /**
   * Import constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger instance.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\okta\Service\User $oktaUser
   *   Okta User service.
   * @param \Drupal\okta_api\Service\Users $oktaApiUsers
   *   Okta API Users Service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(LoggerInterface $logger,
                              EventDispatcherInterface $eventDispatcher,
                              OktaUser $oktaUser,
                              OktaApiUsers $oktaApiUsers,
                              ConfigFactory $config_factory) {
    $this->logger = $logger;
    $this->eventDispatcher = $eventDispatcher;
    $this->oktaUser = $oktaUser;
    $this->oktaApiUsers = $oktaApiUsers;
    $this->okta_config = $config_factory->get('okta.settings');
    $this->okta_import_config = $config_factory->get('okta_import.import');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')->get('okta_import'),
      $container->get('event_dispatcher'),
      $container->get('okta.user'),
      $container->get('okta_api.users'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'okta_import_import';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'okta_import.import',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['okta_import'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Import users by email'),
    ];

    $form['okta_import']['emails_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Emails List'),
      '#default_value' => '',
      '#description' => $this->t('Email addresses, one on each line.'),
    ];

    $form['creds'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Credentials'),
    ];

    $form['creds']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
      '#default_value' => $this->okta_config->get('default_password'),
      '#description' => $this->t('Your password must have<ul><li>8 or more characters</li><li>at least one lowercase letter (a-z)</li><li>at least one uppercase letter (A-Z)</li><li>at least one number (0-9)</li></ul>It must not contain part of your email.'),
    ];

    // TODO Add slightly more helpful description.
    $form['creds']['question'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Security question'),
      '#required' => TRUE,
      '#default_value' => $this->okta_config->get('default_question'),
      '#description' => $this->t('Default Question. Do not screw this up.'),
    ];

    // TODO Add slightly more helpful description.
    $form['creds']['answer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Security answer'),
      '#required' => TRUE,
      '#default_value' => $this->okta_config->get('default_answer'),
      '#description' => $this->t('Default Answer. Do not screw this up.'),
    ];

    $form['activate'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Activation'),
    ];

    $form['activate']['auto_activate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically activate user'),
      '#description' => $this->t('If checked, the user will be activated automatically.'),
      '#default_value' => $this->okta_config->get('auto_activate'),
    ];

    $form['activate']['activation_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user of account activation'),
      '#description' => $this->t('If checked, the user will be sent email notification by OKTA.'),
      '#default_value' => $this->okta_config->get('activation_notify'),
    ];

    $form['app'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Application Assignment'),
    ];

    $form['app']['assign_app'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically assign user to Apps?'),
      '#description' => $this->t('If checked, the user will be auto assigned to application.'),
      '#default_value' => $this->okta_config->get('assign_app'),
    ];

    $form['app']['app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application ID'),
      '#required' => TRUE,
      '#default_value' => $this->okta_config->get('default_app_id'),
      '#description' => $this->t('ID of the application to which the user should be assigned.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $password = $form['creds']['password']['#value'];

    $emailsList = $form_state->getValue('emails_list');
    // Remove line breaks and empty.
    $emails = array_filter(array_map('trim', explode(PHP_EOL, $emailsList)));

    // Check if the password meets our criteria.
    // We are not checking if every.
    // Email supplied is in the password.
    $passwordIsValid = $this->oktaApiUsers->checkPasswordIsValid($password, '');

    if ($passwordIsValid['valid'] == FALSE) {
      $form_state->setError($form['creds']['password'], $passwordIsValid['message']);
      return;
    }

    // TODO Check if emails are valid?
    // TODO.
    // Allow other modules to subscribe to Validate Event.
    $validateEvent = new ValidateUserImportEvent($emails);
    $event = $this->eventDispatcher->dispatch(ValidateUserImportEvent::OKTA_IMPORT_VALIDATEUSERIMPORT, $validateEvent);
    $emails = $event->getEmails();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $emailsList = $form_state->getValue('emails_list');
    $password = $form_state->getValue('password');
    $question = $form_state->getValue('question');
    $answer = $form_state->getValue('answer');
    $assignApp = $form_state->getValue('assign_app');
    $appID = $form_state->getValue('app_id');
    $auto_activate = $form_state->getValue('auto_activate');
    $activation_notify = $form_state->getValue('activation_notify');

    // Remove line breaks and empty.
    $emails = array_filter(array_map('trim', explode(PHP_EOL, $emailsList)));

    foreach ($emails as $email) {
      $user = $this->oktaUser->prepareUser($email, $password, $question, $answer);

      // Allow other modules to subscribe to Pre Submit Event.
      $preUserImportEvent = new PreUserImportEvent($user);
      $preEvent = $this->eventDispatcher->dispatch(PreUserImportEvent::OKTA_IMPORT_PREUSERIMPORT, $preUserImportEvent);
      $user = $preEvent->getUser();

      // Create Okta Users.
      // Only create a new OKTA user if
      // The user is not already registered
      // or skip is false, skip could be false due to number of reasons.
      if ($user['skip_register'] == FALSE) {
        // Attempt to create the user in OKTA.
        $newUser = $this->oktaApiUsers->userCreate(
          $user['profile'],
          $user['credentials'],
          NULL,
          FALSE,
          FALSE);

        if ($newUser != FALSE) {
          // Activate user.
          // Only if not already active.
          if ($auto_activate == TRUE && $newUser != 'ACTIVE') {
            $this->oktaUser->oktaUserService->userActivate($newUser->id, $activation_notify);
          }

          // Add user to OKTA App.
          // TODO Only assign if not already assigned.
          if ($assignApp == TRUE) {
            $this->oktaUser->addUserToApp($newUser, $appID, $assignApp);
          }

          // Set the success message.
          drupal_set_message($this->t('Finished importing user to Okta: @email', ['@email' => $email]));

          // Allow other modules to subscribe to Post Submit Event.
          $postUserImportEvent = new PostUserImportEvent($newUser);
          $this->eventDispatcher->dispatch(PostUserImportEvent::OKTA_IMPORT_POSTUSERIMPORT, $postUserImportEvent);
        }
        else {
          // Set the fail message.
          drupal_set_message($this->t('Failed to import user to Okta: @email', ['@email' => $email]), 'warning');
        }

      }
      else {
        // Set the skip message.
        drupal_set_message($this->t('Skipped to import user to Okta: @email', ['@email' => $email]), 'warning');
      }
    }
  }

}
