<?php

namespace Drupal\okta\Service;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\okta_api\Service\Users;
use Drupal\okta_api\Service\Apps;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Service for provisioning Okta users as part of the signup process.
 */
class User {

  public $oktaUserService;
  protected $oktaAppService;
  protected $config;
  protected $loggerFactory;

  use StringTranslationTrait;

  /**
   * CqcUserService constructor.
   *
   * @param \Drupal\okta_api\Service\Users $oktaUserService
   *   An instance of okta_api Users service.
   * @param \Drupal\okta_api\Service\Apps $oktaAppService
   *   An instance of okta_api Apps service.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   An instance of Config Factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   LoggerChannelFactory.
   */
  public function __construct(Users $oktaUserService,
                              Apps $oktaAppService,
                              ConfigFactory $config,
                              LoggerChannelFactory $loggerFactory) {
    $this->oktaUserService = $oktaUserService;
    $this->oktaAppService = $oktaAppService;
    $this->config = $config->getEditable('okta.settings');
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * Creates a user in Okta.
   */
  public function createUser($firstName, $lastName, $email, $password) {
    $profile = $this->oktaUserService->buildProfile($firstName, $lastName, $email, $email);

    $credentials = $this->oktaUserService->buildCredentials(
      $password, [
        'question' => $this->config->get('default_answer'),
        'answer' => $this->config->get('default_answer'),
      ]);

    $newUser = $this->oktaUserService->userCreate($profile, $credentials, NULL, FALSE);

    return $newUser;
  }

  /**
   * Adds an Okta user to an app.
   *
   * @param object $user
   *   User.
   * @param string $app_id
   *   App id.
   * @param bool $assign_app
   *   Auto assign app?
   *
   * @return bool|object
   *   Obj or False
   */
  public function addUserToApp($user, $app_id = '', $assign_app = TRUE) {
    if ($app_id == '') {
      $this->config->get('default_app_id');
    }

    if ($app_id == '' || $assign_app == FALSE) {
      return FALSE;
    }

    $credentials = [
      'id' => $user->id,
      'scope' => 'USER',
      'credentials' => ['userName' => $user->profile->email],
    ];

    $addToOktaApp = $this->oktaAppService->assignUsersToApp($app_id, $credentials);

    if ($addToOktaApp != FALSE) {
      // Log success.
      $this->loggerFactory->get('okta')->error(
        "@message",
        [
          '@message' => 'Assigned app to user: ' . $user->profile->email,
        ]
      );
    }
    else {
      // Log fail.
      $this->loggerFactory->get('okta')->error(
        "@message",
        [
          '@message' => 'Failed to assign app to user: ' . $user->profile->email,
        ]
      );
    }

    return $addToOktaApp;
  }

  /**
   * Prepare Okta User.
   *
   * @param string $email
   *   Email.
   * @param string $password
   *   Pass.
   * @param string $question
   *   Default Question.
   * @param string $answer
   *   Default Answer.
   * @param string $firstName
   *   First Name.
   * @param string $lastName
   *   Last Name.
   *
   * @return array
   *   Okta User array.
   */
  public function prepareUser($email,
                              $password = '',
                              $question = '',
                              $answer = '',
                              $firstName = '',
                              $lastName = '') {
    // Default FName?
    if ($firstName == '') {
      $firstName = $this->config->get('default_fname');
    }

    // Default LName?
    if ($lastName == '') {
      $lastName = $this->config->get('default_lname');
    }

    // Default Password?
    if ($password == '') {
      $password = $this->config->get('default_password');
    }

    // Default Question?
    if ($question == '') {
      $question = $this->config->get('default_question');
    }

    // Default Answer?
    if ($answer == '') {
      $answer = $this->config->get('default_answer');
    }

    // Create the profile.
    $profile = $this->oktaUserService->buildProfile($firstName, $lastName, $email, $email);

    // Create the credentials.
    $credentials = $this->oktaUserService->buildCredentials(
      $password,
      [
        'question' => $question,
        'answer' => $answer,
      ]
    );

    $user = [
      'profile' => $profile,
      'credentials' => $credentials,
      'skip_register' => FALSE,
    ];

    return $user;
  }

}
