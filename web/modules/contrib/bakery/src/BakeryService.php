<?php

namespace Drupal\bakery;

/**
 * @file
 * Services used in  bakery SSO functions.
 */

use Drupal\user\Entity\User;
use Drupal\Core\Form\FormState;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Url;

/**
 * Common functionalities used in both controller and module.
 */
class BakeryService {

  protected $db;

  protected $config;

  /**
   * When the service is created, set a value for the example variable.
   */
  public function __construct(Connection $db, ConfigFactory $configFactory) {
    $this->db = $db;
    $this->config = $configFactory->get('bakery.settings');
  }

  /**
   * Finalize the login process. Must be called when logging in a user.
   *
   * The function records a watchdog message about the new session, saves the
   * login timestamp, calls hook_user op 'login' and generates a new session.
   *
   * $param $edit
   *   This array is passed to hook_user op login.
   */
  public function authenticateFinalize(&$edit) {
    $user = \Drupal::currentUser();
    \Drupal::logger('user')->notice('Session opened for %name.', array('%name' => $user->getUsername()));
    // Update the user table timestamp noting user has logged in.
    // This is also used to invalidate one-time login links.
    // $user->login = time();
    $this->db->update('users_field_data')
      ->fields(array('login' => $user->login))
      ->condition('uid', $user->id())
      ->execute();
    // Regenerate the session ID to prevent against session fixation attacks.
    // drupal_session_regenerate();
    // \Drupal::moduleHandler()->invokeAll('user_login', [$user]);
    // user_module_invoke('login', $edit, $user);.
  }

  /**
   * Create a new cookie for identification.
   */
  public function bakeChocolatechipCookie($name, $mail, $init) {
    $key = $this->config->get('bakery_key');
    if (!empty($key)) {
      $cookie = array();
      $cookie['name'] = $name;
      $cookie['mail'] = $mail;
      $cookie['init'] = $init;
      $cookie['master'] = $this->config->get('bakery_is_master');
      $cookie['calories'] = 480;
      $cookie['timestamp'] = $_SERVER['REQUEST_TIME'];
      $cookie_secure = ini_get('session.cookie_secure');
      $type = $this->cookieName('CHOCOLATECHIP');
      $cookie['type'] = $type;
      $data = $this->bakeData($cookie);
      setcookie($type, $data, $_SERVER['REQUEST_TIME'] + $this->config->get('bakery_freshness'), '/', $this->config->get('bakery_domain'), (empty($cookie_secure) ? FALSE : TRUE));
    }
  }

  /**
   * Create a cookie for passing information between sites.
   *
   * This is for registration and login.
   */
  public function bakeOatmealCookie($name, $data) {

    $key = $this->config->get('bakery_key');

    if (!empty($key)) {
      global $base_url;
      $cookie = array(
        'data' => $data,
        'name' => $name,
        'calories' => 320,
        'timestamp' => $_SERVER['REQUEST_TIME'],
      );
      if ($this->config->get('bakery_is_master')) {
        $cookie['master'] = 1;
      }
      else {
        $cookie['master'] = 0;
        // Match the way slaves are set in Bakery settings, with ending slash.
        $cookie['slave'] = $base_url . '/';
      }
      $cookie_secure = ini_get('session.cookie_secure');
      $type = $this->cookieName('OATMEAL');
      $cookie['type'] = $type;
      $data = $this->bakeData($cookie);
      setcookie($type, $data, $_SERVER['REQUEST_TIME'] + $this->config->get('bakery_freshness'), '/', $this->config->get('bakery_domain'), (empty($cookie_secure) ? FALSE : TRUE));
    }
  }

  /**
   * Build internal init url (without scheme).
   */
  public function initField($uid) {
    $url = $this->config->get('bakery_master');
    $scheme = parse_url($url, PHP_URL_SCHEME);
    return str_replace($scheme . '://', '', $url) . 'user/' . $uid . '/edit';
  }

  /**
   * Encrypt and sign data for Bakery transfer.
   *
   * @param array $data
   *   Array of data to be transferred.
   *
   * @return string
   *   String of signed and encrypted data, url safe.
   */
  public function bakeData($data) {
    $key = $this->config->get('bakery_key');
    $data = $this->bakeryEncrypt(serialize($data));
    $signature = hash_hmac('sha256', $data, $key);
    return base64_encode($signature . $data);
  }

  /**
   * Destroy unwanted cookies.
   */
  public function eatCookie($type = 'CHOCOLATECHIP') {
    $cookie_secure = ini_get('session.cookie_secure');
    $type = $this->cookieName($type);
    setcookie($type, '', $_SERVER['REQUEST_TIME'] - 3600, '/', '', (empty($cookie_secure) ? FALSE : TRUE));
    setcookie($type, '', $_SERVER['REQUEST_TIME'] - 3600, '/', $this->config->get('bakery_domain'), (empty($cookie_secure) ? FALSE : TRUE));
  }

  /**
   * Name for cookie including session.cookie_secure and variable extension.
   *
   * @param string $type
   *   CHOCOLATECHIP or OATMEAL, default CHOCOLATECHIP.
   *
   * @return string
   *   The cookie name for this environment.
   */
  public function cookieName($type = 'CHOCOLATECHIP') {
    // Use different names for HTTPS and HTTP to prevent a cookie collision.
    if (ini_get('session.cookie_secure')) {
      $type .= 'SSL';
    }
    // Allow installation to modify the cookie name.
    $extension = $this->config->get('bakery_cookie_extension') || '';
    $type .= $extension;
    return $type;
  }

  /**
   * Validate signature and decrypt data.
   *
   * @param string $data
   *   String of Bakery data, base64 encoded.
   * @param string $type
   *   Optional string defining the type of data this is.
   *
   * @return bool
   *   Unserialized data or FALSE if invalid.
   */
  public function validateData($data, $type = NULL) {
    $key = $this->config->get('bakery_key');
    $data = base64_decode($data);
    $signature = substr($data, 0, 64);
    $encrypted_data = substr($data, 64);
    if ($signature !== hash_hmac('sha256', $encrypted_data, $key)) {
      return FALSE;
    }
    $decrypted_data = unserialize($this->bakeryDecrypt($encrypted_data));
    // Prevent one cookie being used in place of another.
    if ($type !== NULL && $decrypted_data['type'] !== $type) {
      return FALSE;
    }
    if ($decrypted_data['timestamp'] + $this->config->get('bakery_freshness') >= $_SERVER['REQUEST_TIME']) {
      return $decrypted_data;
    }
    return FALSE;
  }

  /**
   * Perform standard Drupal login operations for a user object.
   *
   * The user object must already be authenticated. This function verifies
   * that the user account is not blocked/denied and then performs the login,
   * updates the login timestamp in the database, invokes hook_user('login'),
   * and regenerates the session.
   *
   * @param object $account
   *    An authenticated user object to be set as the currently logged
   *    in user.
   * @param array $edit
   *    The array of form values submitted by the user, if any.
   *    This array is passed to hook_user op login.
   *
   * @return bool
   *    TRUE if the login succeeds, FALSE otherwise.
   */
  public function userExternalLogin($account, $edit = array()) {
    $form = \Drupal::formBuilder()->getForm('Drupal\user\Form\UserLoginForm');
    $state['values'] = $edit;
    if (empty($state['values']['name'])) {
      $form_state = new FormState();
      $form_state->setRebuild();
      \Drupal::formBuilder()->buildForm('Drupal\user\Form\UserLoginForm', $form_state);
      $form_state->setValue('name', $account->getUsername());
      $state['values']['name'] = $account->getUsername();
    }
    // Check if user is blocked or denied by access rules.
    \Drupal::formBuilder()->validateForm($form_id, $form, $form_state);
    if ($form_state->getErrors()) {
      // Invalid login.
      return FALSE;
    }
    // Valid login.
    user_login_finalize($account);
    // $this->authenticateFinalize($state['values']);.
    return TRUE;
  }

  /**
   * Test identification cookie.
   */
  public function tasteChocolatechipCookie() {

    $cookie = $this->validateCookie();

    // Continue if this is a valid cookie.
    // That only happens for users who have
    // a current valid session on the master site.
    if ($cookie) {
      $destroy_cookie = FALSE;
      $user = \Drupal::currentUser();

      // Detect SSO cookie mismatch if there is
      // already a valid session for user.
      if ($user->id() && $cookie['name'] !== $user->getUsername()) {
        // The SSO cookie doesn't match the existing session so force a logout.
        // drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
        user_logout();
      }

      // Bake a fresh cookie. Yum.
      $this->bakeChocolatechipCookie($cookie['name'], $cookie['mail'], $cookie['init']);
      if ($user->id() == 0) {
        // Since this might happen in hook_boot we need to bootstrap first.
        // Note that this only runs if they have a valid session on the master
        // and do not have one on the slave so it only creates the extra load of
        // a bootstrap on one pageview per session on the site
        // which is not much.
        // drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
        // User is anonymous. If they do not have an account we'll create one by
        // requesting their information from the master site. If they do have an
        // account we may need to correct some disparant information.
        $account = \Drupal::entityManager()
          ->getStorage('user')
          ->loadByProperties(array(
            'name' => $cookie['name'],
            'mail' => $cookie['mail'],
          ));
        $account = reset($account);
        // Fix out of sync users with valid init.
        if (!$account && $this->config->get('bakery_is_master') == 0 && $cookie['master'] == 0) {
          $count = $this->db->select('users_field_data', 'u')->fields('u', array('uid'))
            ->condition('init', $cookie['init'])
            ->countQuery()->execute()->fetchField();
          if ($count > 1) {
            // Uh oh.
            \Drupal::logger('bakery')->notice('Account uniqueness problem: Multiple users found with init %init.', array('%init' => $cookie['init']));
            drupal_set_message(t('Account uniqueness problem detected. <a href="@contact">Please contact the site administrator.</a>', array('@contact' => $this->config->get('bakery_master') . 'contact')), 'error');
          }
          if ($count == 1) {
            $account = \Drupal::entityManager()->getStorage('user')->loadByProperties(array('init' => $cookie['init']));
            if (is_array($account)) {
              $account = reset($account);
            }
            if ($account) {
              \Drupal::logger('bakery')
                ->notice('Fixing out of sync uid %uid. Changed name %name_old to %name_new, mail %mail_old to %mail_new.', array(
                  '%uid' => $account->id(),
                  '%name_old' => $account->getUsername(),
                  '%name_new' => $cookie['name'],
                  '%mail_old' => $account->getEmail(),
                  '%mail_new' => $cookie['mail'],
                )
                );
              $account->setEmail($cookie['mail']);
              $account->setUsername($cookie['name']);
              $account->save();
              $account = \Drupal::entityManager()->getStorage('user')->loadByProperties(
                array(
                  'name' => $cookie['name'],
                  'mail' => $cookie['mail'],
                )
              );
              $account = reset($account);
            }
          }
        }
        // Create the account if it doesn't exist.
        if (!$account && $this->config->get('bakery_is_master') == 0 && $cookie['master'] == 0) {
          $checks = TRUE;
          $mail_count = db_select('users_field_data', 'u')->fields('u', array('uid'))
            ->condition('uid', $user->id(), '!=')
            ->condition('mail', '', '!=')
            ->where('LOWER(mail) = LOWER(:mail)', array(':mail' => $cookie['mail']))
            ->countQuery()->execute()->fetchField();
          if ($mail_count > 0) {
            $checks = FALSE;
          }
          $name_count = db_select('users_field_data', 'u')->fields('u', array('uid'))
            ->condition('uid', $user->id(), '!=')
            ->where('LOWER(name) = LOWER(:name)', array(':name' => $cookie['name']))
            ->countQuery()->execute()->fetchField();
          if ($name_count > 0) {
            $checks = FALSE;
          }
          $init_count = db_select('users_field_data', 'u')->fields('u', array('uid'))
            ->condition('uid', $user->id(), '!=')
            ->condition('init', $cookie['init'], '=')
            ->where('LOWER(name) = LOWER(:name)', array(':name' => $cookie['name']))
            ->countQuery()->execute()->fetchField();
          if ($init_count > 0) {
            $checks = FALSE;
          }
          if ($checks) {
            // Request information from master to keep data in sync.
            $uid = $this->requestAccount($cookie['name']);
            // In case the account creation failed we want to make sure the user
            // gets their bad cookie destroyed by not returning too early.
            if ($uid) {
              $account = \Drupal::entityManager()->getStorage('user')->load($uid);
            }
            else {
              $destroy_cookie = TRUE;
            }
          }
          else {
            // @FIXME
            // url() expects a route name or an external URI.
            drupal_set_message(t('Your user account on %site appears to have problems. Would you like to try to <a href="@url">repair it yourself</a>?', array('%site' => \Drupal::config('system.site')->get('name'), '@url' => \Drupal::url('bakery.repair'))));

            drupal_set_message(Xss::filter($this->config->get('bakery_help_text')));
            $_SESSION['BAKERY_CRUMBLED'] = TRUE;
          }

        }
        if ($account && $cookie['master'] && $account->id() && !$this->config->get('bakery_is_master') && $account->get('init')->value != $cookie['init']) {
          // User existed previously but init is wrong.
          // Fix it to ensure account remains in sync.
          // Make sure that there aren't any
          // OTHER accounts with this init already.
          $count = $this->db->select('users_field_data', 'u')
                   ->fields('u', array('uid'))
                   ->condition('init', $cookie['init'], '=')
                   ->countQuery()->execute()->fetchField();
          if ($count == 0) {
            db_update('users_field_data')
              ->fields(array('init' => $cookie['init']))
              ->condition('uid', $account->id())
              ->execute();
            \Drupal::logger('bakery')
              ->notice('uid %uid out of sync. Changed init field from %oldinit to %newinit', array(
                '%oldinit' => $account->init,
                '%newinit' => $cookie['init'],
                '%uid' => $account->id(),
              ));
          }
          else {
            // Username and email matched,
            // but init belonged to a DIFFERENT account.
            // Something got seriously tangled up.
            \Drupal::logger('bakery')
              ->notice('Accounts mixed up! Username %user and init %init disagree with each other!', array(
                '%user' => $account->getUsername(),
                '%init' => $cookie['init'],
              )
              );
          }
        }

        if ($account && $user->id() == 0) {
          // If the login attempt fails we need to destroy the cookie to prevent
          // infinite redirects (with infinite failed login messages).
          $login = $this->userExternalLogin($account);
          if ($login) {
            // If an anonymous user has just been logged in,
            // trigger a 'refresh' of the current page,
            // ensuring that drupal_goto() does not override
            // the current page with the destination query.
            // UrlHelper::filterQueryParameters
            // $query = drupal_get_query_parameters();
            // unset($_GET['destination']);
            // $current_path = \Drupal::service('path.current')->getPath();
            return new RedirectResponse(new Url('user.page'));
            // Return new RedirectResponse(current_path(),
            // array('query' => $query));.
          }
          else {
            $destroy_cookie = TRUE;
          }
        }
      }
      if ($destroy_cookie !== TRUE) {
        return TRUE;
      }
    }

    // Eat the bad cookie. Burp.
    if ($cookie === FALSE) {
      $this->eatCookie();
    }

    // No cookie or invalid cookie.
    if (!$cookie) {
      $user = \Drupal::currentUser();
      // Log out users that have lost their SSO cookie, with the exception of
      // UID 1 and any applied roles with permission to bypass.
      if ($user->id() > 1) {
        // This runs for logged in users.
        // Those folks are going to get a full bootstrap anyway
        // so this isn't a problem.
        // drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
        if (!\Drupal::currentUser()->hasPermission('bypass bakery')) {
          \Drupal::logger('bakery')->notice('Logging out the user with the bad cookie.', []);
          user_logout();
        }
      }
    }

    return FALSE;
  }

  /**
   * Function to validate cookies.
   *
   * @param string $type
   *   CHOCOLATECHIP or OATMEAL, default CHOCOLATECHIP.
   *
   * @return bool
   *   the validated and decrypted cookie in an array or FALSE.
   */
  public function validateCookie($type = 'CHOCOLATECHIP') {
    $key = $this->config->get('bakery_key');

    $type = $this->cookieName($type);
    if (!isset($_COOKIE[$type]) || !$key || !$this->config->get('bakery_domain')) {
      return FALSE;
    }

    if (($data = $this->validateData($_COOKIE[$type], $type)) !== FALSE) {
      return $data;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Request account information from master to create account locally.
   *
   * @param string $name
   *   The username or e-mail to request information for to create.
   * @param bool $or_email
   *   Load account by name or email. Useful for getting
   *   account data from a password request where you get name or email.
   *
   * @return bool
   *   The newly created local UID or FALSE.
   */
  public function requestAccount($name, $or_email = FALSE) {
    global $base_url;

    $existing_account = user_load_by_name($name);
    if (!$existing_account && $or_email) {
      $account = user_load_by_mail($name);
    }
    // We return FALSE in cases that the account already exists locally or if
    // there was an error along the way of requesting and creating it.
    if ($existing_account) {
      return FALSE;
    }
    $master = $this->config->get('bakery_master');
    $key = $this->config->get('bakery_key');

    // Save a stub account so we have a slave UID to send.
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $account = User::create();
    // Mandatory settings.
    $account->setPassword(user_password());
    $account->enforceIsNew();
    $account->setEmail('email');
    // This username must be unique and accept only a-Z,0-9, - _ @ .
    $account->setUsername($name);
    // Optional settings.
    $account->set("init", 'bakery_temp/' . mt_rand());
    $account->set("langcode", $language);
    $account->set("preferred_langcode", $language);
    $account->set("preferred_admin_langcode", $language);
    // $user->set("setting_name", 'setting_value');.
    $account->activate();
    // Save user.
    $account->save();
    if (!$account) {
      \Drupal::logger('bakery')->error('Unable to create stub account for @name', array('@name' => $name));
      return FALSE;
    }
    $stub_uid = $account->id();

    $type = 'gingerbread';
    $payload = array();
    $payload['name'] = $name;
    $payload['or_email'] = $or_email;
    // Match how slaves are set on the master.
    $payload['slave'] = rtrim($base_url, '/') . '/';
    $payload['uid'] = $account->id();
    $payload['timestamp'] = $_SERVER['REQUEST_TIME'];
    $payload['type'] = $type;
    $data = $this->bakeData($payload);
    // $payload = UrlHelper::buildQuery(array($type => $data));
    // Make request to master for account information.
    $client = \Drupal::httpClient();
    try {
      $response = $client->post($master . 'bakery/create', ["form_params" => [$type => $data]]);
    }
    catch (BadResponseException $exception) {
      $response = $exception->getResponse();
      Drupal::logger('bakery')->error(t('Failed to fetch file due to HTTP error "%error"', array('%error' => $response->getStatusCode() . ' ' . $response->getReasonPhrase())), 'error');
      return FALSE;
    }
    catch (RequestException $exception) {
      Drupal::logger('bakery')->error(t('Failed to fetch file due to error "%error"', array('%error' => $exception->getMessage())), 'error');
      return FALSE;
    }
    // $result = drupal_http_request($master . 'bakery/create', $http_options);
    // Parse result and create account.
    if ($response->getStatusCode() != 200) {
      $message = $response->getBody();
      \Drupal::logger('bakery')->error('Received response !code from master with message @message', array('!code' => $result->code, '@message' => $message));
      $account->delete();
      return FALSE;
    }

    if (($cookie = $this->validateData($response->getBody())) === FALSE) {
      // Invalid response.
      \Drupal::logger('bakery')->error('Invalid response from master when attempting to create local account for @name', array('@name' => $name));
      $account->delete();
      return FALSE;
    }
    // Create account.
    $account->set("init", $cookie['uid']);
    foreach ($this->config->get('bakery_supported_fields') as $type => $enabled) {
      if ($enabled && isset($cookie[$type])) {
        switch ($type) {
          case 'name':
              $account->setUsername($cookie['name']);
            break;

          case 'mail':
              $account->setEmail($cookie['mail']);
            break;
        }
      }
    }
    // Save user.
    $account->save();

    if ($account) {
      \Drupal::logger('bakery')->notice('Created account for @name', array('@name' => $name));
      return $account->id();
    }

    \Drupal::logger('bakery')->error('Unable to create account for @name', array('@name' => $name));
    $account->delete();
    return FALSE;
  }

  /**
   * Encryption handler.
   *
   * @param string $text
   *   The text to be encrypted.
   *
   * @return sting
   *   Encryped text.
   */
  private function bakeryEncrypt($text) {
    $key = \Drupal::config('bakery.settings')->get('bakery_key');

    $td = mcrypt_module_open('rijndael-128', '', 'ecb', '');
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

    $key = substr($key, 0, mcrypt_enc_get_key_size($td));

    mcrypt_generic_init($td, $key, $iv);

    $data = mcrypt_generic($td, $text);

    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    return $data;
  }

  /**
   * Decryption handler.
   *
   * @param string $text
   *   The data to be decrypted.
   *
   * @return string
   *   Decrypted text.
   */
  private function bakeryDecrypt($text) {
    $key = \Drupal::config('bakery.settings')->get('bakery_key');

    $td = mcrypt_module_open('rijndael-128', '', 'ecb', '');
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

    $key = substr($key, 0, mcrypt_enc_get_key_size($td));

    mcrypt_generic_init($td, $key, $iv);

    $data = mdecrypt_generic($td, $text);

    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    return $data;
  }

}
