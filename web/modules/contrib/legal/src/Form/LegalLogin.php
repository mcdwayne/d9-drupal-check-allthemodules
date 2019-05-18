<?php

namespace Drupal\legal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\legal\Entity\Accepted;
use Drupal\user\Entity\User;
use Drupal\Component\Utility\Crypt;

/**
 * After login display new T&Cs to user and require that they are agreed to.
 *
 * User has been logged out before arriving at this page,
 * and is logged back in if they accept T&Cs.
 */
class LegalLogin extends FormBase {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Language handling.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The account the shortcut set is for.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'legal_login';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config   = $this->config('legal.settings');
    $language = \Drupal::languageManager()->getCurrentLanguage();

    $id_hash = $_COOKIE['Drupal_visitor_legal_hash'];
    $uid     = $_COOKIE['Drupal_visitor_legal_id'];
    $token   = $_GET['token'];

    // Get last accepted version for this account.
    $legal_account = legal_get_accept($uid);

    // If no version accepted, get version with current language revision.
    if (empty($legal_account['version'])) {
      $conditions = legal_get_conditions($language->getId());
      // No conditions set yet.
      if (empty($conditions['conditions'])) {
        return;
      }
    }
    else {
      // Get version / revision of last accepted language.
      $conditions = legal_get_conditions($legal_account['language']);
      // No conditions set yet.
      if (empty($conditions['conditions'])) {
        return;
      }
      // Check latest version of T&C has been accepted.
      $accepted = legal_version_check($uid, $conditions['version'], $conditions['revision'], $legal_account);

      if ($accepted) {

        if ($config->get('accept_every_login') == 0) {
          return;
        }
        else {
          $request        = \Drupal::request();
          $session        = $request->getSession();
          $newly_accepted = $session->get('legal_login', FALSE);

          if ($newly_accepted) {
            return;
          }
        }

      }
    }

    legal_display_fields($form, $conditions, 'login');

    $form['uid'] = array(
      '#type'  => 'value',
      '#value' => $uid,
    );

    $form['token'] = array(
      '#type'  => 'value',
      '#value' => $token,
    );

    $form['hash'] = array(
      '#type'  => 'value',
      '#value' => $id_hash,
    );

    $form['tc_id'] = array(
      '#type'  => 'value',
      '#value' => $conditions['tc_id'],
    );

    $form['version'] = array(
      '#type'  => 'value',
      '#value' => $conditions['version'],
    );

    $form['revision'] = array(
      '#type'  => 'value',
      '#value' => $conditions['revision'],
    );

    $form['language'] = array(
      '#type'  => 'value',
      '#value' => $conditions['language'],
    );

    $form = legal_display_changes($form, $uid);

    $form['save'] = array(
      '#type'   => 'submit',
      '#value'  => t('Confirm'),
      '#weight' => 100,
    );

    // Prevent this page from being cached.
    \Drupal::service('page_cache_kill_switch')->trigger();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $token = $form_state->getValue('token');

    $uid        = $form_state->getValue('uid');
    $account    = User::load($uid);
    $this->user = $account;

    $last_login = $account->get('login')->value;
    $password   = $account->get('pass')->value;
    $data       = $last_login . $uid . $password;

    $hash = Crypt::hmacBase64($data, $token);

    if ($hash != $form_state->getValue('hash')) {
      $form_state->setErrorByName('legal_accept', $this->t('User ID cannot be identified.'));
      legal_deny_with_redirect();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    user_cookie_delete('legal_hash');
    user_cookie_delete('legal_id');

    $values   = $form_state->getValues();
    $user     = $this->user;
    $redirect = '/user/' . $values['uid'];
    $config   = $this->config('legal.settings');

    if (!empty($_GET['destination'])) {
      $redirect = $_GET['destination'];
    }

    $form_state->setRedirectUrl(Url::fromUserInput($redirect));

    // Option to require user to accept T&Cs on every login.
    if ($config->get('accept_every_login') == '1') {

      // Set flag that user has accepted T&Cs again.
      $request = \Drupal::request();
      $session = $request->getSession();
      $session->set('legal_login', TRUE);

      // Get last accepted version for this account.
      $legal_account    = legal_get_accept($values['uid']);
      $already_accepted = legal_version_check($values['uid'], $values['version'], $values['revision'], $legal_account);

      // If already accepted just update the time.
      if ($already_accepted) {
        $accepted = Accepted::load($legal_account['legal_id']);
        $accepted->set("accepted", time());
        $accepted->save();
      }
      else {
        legal_save_accept($values['version'], $values['revision'], $values['language'], $values['uid']);
      }
    }
    else {
      legal_save_accept($values['version'], $values['revision'], $values['language'], $values['uid']);
    }

    $this->logger('legal')
      ->notice('%name accepted T&C version %tc_id.', array(
        '%name'  => $user->get('name')->getString(),
        '%tc_id' => $values['tc_id'],
      ));

    // User has new permissions, so we clear their menu cache.
    \Drupal::cache('menu')->delete($values['uid']);

    // Log user in.
    user_login_finalize($user);
  }

  /**
   * Access control callback.
   *
   * Check that access cookie and hash have been set.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account) {

    // Check we have all the data and there are no shenanigans.
    if (!isset($_GET['token']) || !isset($_COOKIE['Drupal_visitor_legal_id']) || !is_numeric($_COOKIE['Drupal_visitor_legal_id']) || !isset($_COOKIE['Drupal_visitor_legal_hash'])) {
      return AccessResult::forbidden();
    }

    $visitor    = User::load($_COOKIE['Drupal_visitor_legal_id']);
    $last_login = $visitor->get('login')->value;

    if (empty($last_login)) {
      return AccessResult::forbidden();
    }

    // Limit how long $id_hash can be used to 1 hour.
    // Timestamp and $id_hash are used to generate the authentication token.
    if ((\Drupal::time()->getRequestTime() - $last_login) > 3600) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

}
