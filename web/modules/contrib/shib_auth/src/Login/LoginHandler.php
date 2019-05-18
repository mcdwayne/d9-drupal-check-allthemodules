<?php

namespace Drupal\shib_auth\Login;

use Drupal\Component\Utility\Random;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\PrivateTempStoreFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class LoginHandler.
 *
 * @package Drupal\shib_auth
 */
class LoginHandler implements LoginHandlerInterface {

  /**
   * @var
   */
  protected $user;
  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $user_store;
  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;
  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $adv_config;
  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;
  /**
   * @var \Drupal\shib_auth\Login\ShibSessionVars
   */
  protected $shib_session;
  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $shib_logger;

  /**
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $temp_store_factory;
  /**
   * @var \Drupal\user\PrivateTempStore
   */
  protected $custom_data_store;

  /**
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $session_manager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $current_user;

  /**
   * @var string
   */
  protected $error_message;

  /**
   * MySQL error code.
   */
  const MYSQL_ER_DUP_KEY = 23000;

  /**
   * LoginHandler constructor.
   *
   * @param \Drupal\Core\Database\Connection $db
   * @param \Drupal\Core\Config\ImmutableConfig $config
   * @param \Drupal\Core\Config\ImmutableConfig $advanced_config
   */
  public function __construct(Connection $db, ImmutableConfig $config, ImmutableConfig $adv_config, EntityTypeManagerInterface $etm, ShibSessionVars $shib_session, LoggerInterface $shib_logger, PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user) {
    $this->db = $db;
    $this->config = $config;
    $this->adv_config = $adv_config;
    $this->user_store = $etm->getStorage('user');
    $this->shib_session = $shib_session;
    $this->shib_logger = $shib_logger;
    $this->temp_store_factory = $temp_store_factory;
    $this->session_manager = $session_manager;
    $this->current_user = $current_user;
    $this->custom_data_store = $this->temp_store_factory->get('shib_auth');

    // Start Session if it does not exist yet.
    if ($this->current_user->isAnonymous() && !isset($_SESSION['session_started'])) {
      $_SESSION['session_started'] = TRUE;
      $this->session_manager->start();
    }
  }

  /**
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function shibLogin() {

    try {
      // Register new user if user does not exist.
      if (!$this->checkUserExists()) {

        // Use the Shib email, if we've got it.
        if (!empty($this->shib_session->getEmail())) {
          // Add custom Email to the session.
          $this->custom_data_store->set('custom_email', $this->shib_session->getEmail());
        }

        // Check if custom email has been set.
        if (!$this->custom_data_store->get('custom_email')) {
          $this->custom_data_store->set('return_url', \Drupal::request()->getRequestUri());
          // Redirect to email form if custom email has not been set.
          $response = new RedirectResponse(Url::fromRoute('shib_auth.custom_data_form')
            ->toString());
          return $response;
        }
        else {
          $user_registered = $this->registerNewUser();
        }

      }
      else {
        $user_registered = TRUE;
      }

      if ($user_registered) {
        $this->authenticateUser();
        return FALSE;
      }

    }
    catch (\Exception $e) {
      // Log the error to Drupal log messages.
      $this->shib_logger->error($e);

      $user = \Drupal::currentUser();
      if ($user->isAuthenticated()) {
        // Kill the drupal session.
        // @todo - Do we need to kill the session for anonymous users, too? If so, how do we set the error message?
        user_logout();
      }

      if ($this->getErrorMessage()) {
        drupal_set_message($this->getErrorMessage(), 'error');
      }

      $return_url = '';
      if ($this->adv_config->get('url_redirect_logout')) {
        $return_url = '?return=' . $this->adv_config->get('url_redirect_logout');
      }
      // Redirect to shib logout url.
      return new RedirectResponse($this->config->get('shibboleth_logout_handler_url') . $return_url);
    }

    return FALSE;

  }

  /**
   * Adds user to the shib_auth table in the database.
   *
   * @param bool $success
   *
   * @return bool
   *
   * @throws \Exception
   */
  private function registerNewUser($success = FALSE) {

    $user_data = [
      'name' => $this->shib_session->getTargetedId(),
      'mail' => $this->custom_data_store->get('custom_email'),
      'pass' => $this->genPassword(),
      'status' => 1,
    ];

    try {

      // Create Drupal user.
      $this->user = $this->user_store->create($user_data);
      if (!$results = $this->user->save()) {
        // Throw exception if Drupal user creation fails.
        throw new \Exception();
      }

    }
    catch (\Exception $e) {
      if ($e->getCode() == self::MYSQL_ER_DUP_KEY) {
        $this->setErrorMessage(t('There was an error creating your user. A user with your email address already exists.'));
        throw new \Exception('Error creating new Drupal user from Shibboleth Session. Duplicate user row.');
      }
      else {
        $this->setErrorMessage(t('There was an error creating your user.'));
        throw new \Exception('Error creating new Drupal user from Shibboleth Session.');
      }
    }

    try {

      // Insert shib data into shib_authmap table.
      $shib_data = [
        'uid' => $this->user->id(),
        'targeted_id' => $this->shib_session->getTargetedId(),
        'idp' => $this->shib_session->getIdp(),
        'created' => REQUEST_TIME,
      ];

      if (!$success = $this->db->insert('shib_authmap')->fields($shib_data)->execute()) {
        // Throw exception if shib_authmap insert fails.
        throw new \Exception();
      }

    }
    catch (\Exception $e) {
      $this->setErrorMessage(t('There was an error creating your user.'));
      throw new \Exception('Error creating new Drupal user from Shibboleth Session. Database insert on shib_authmap failed.');
    }

    return TRUE;
  }

  /**
   * Finalize user login.
   *
   * @return bool
   *
   * @throws \Exception
   */
  private function authenticateUser() {
    if (empty($this->user)) {
      $this->setErrorMessage(t('There was an error logging you in.'));
      throw new \Exception('No uid found for user when trying to initialize Drupal session.');
    }
    user_login_finalize($this->user);
    return TRUE;
  }

  /**
   * Check shib_authmap table for user, return true if user found.
   *
   * @return bool
   *
   * @throws \Exception
   */
  private function checkUserExists() {
    $user_query = $this->db->select('shib_authmap');
    $user_query->fields('shib_authmap', ['id', 'uid', 'targeted_id']);
    $user_query->condition('targeted_id', $this->shib_session->getTargetedId());
    $results = $user_query->execute()->fetchAll();

    if (empty($results)) {
      // No user found.
      return FALSE;
    }

    if (count($results) > 1) {
      $this->setErrorMessage(t('There was an error logging you in.'));
      throw new \Exception('Multiple entries for a user exist in the shib_authmap table.');
    }

    $this->user = User::load($results[0]->uid);

    if (empty($this->user)) {
      $this->setErrorMessage(t('There was an error logging you in.'));
      throw new \Exception('User information exists in shib_authmap table, but Drupal user does not exist.');
    }
    return TRUE;
  }

  /**
   * Generate a random password for the Drupal user account.
   *
   * @return string
   */
  private function genPassword() {
    $rand = new Random();
    return $rand->string(30);
  }

  /**
   * @return \Drupal\shib_auth\Login\ShibSessionVars
   */
  public function getShibSession() {
    return $this->shib_session;
  }

  /**
   *
   */
  private function setErrorMessage($message) {
    $this->error_message = $message;
  }

  /**
   *
   */
  private function getErrorMessage() {
    return $this->error_message;
  }

}
