<?php

namespace Drupal\ulogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\externalauth\ExternalAuth;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\ulogin\UloginHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\user\Entity\User;

/**
 * Controller routines for user routes.
 */
class UloginController extends ControllerBase {

  /**
   * Returns report.
   *
   * @return array
   *   Report table.
   */
  public function uloginReport() {
    $providers = UloginHelper::providersList();

    $header = [$this->t('Authentication provider'), $this->t('Users count')];
    $rows = [];
    $query = \Drupal::database()->select('ulogin_identity', 'ul_id');
    $query->addField('ul_id', 'network', 'network');
    $query->addExpression('COUNT(ulogin_uid)', 'count');
    $query->groupBy('network');
    $results = $query->execute()
      ->fetchAllAssoc('network', \PDO::FETCH_ASSOC);
    foreach ($results as $result) {
      $rows[] = [
        $providers[$result['network']],
        $result['count'],
      ];
    }

    $build = [];

    $build['report'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

  /**
   * Call back for login and registration.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect.
   */
  public function uloginCallback() {
    $get_token = \Drupal::request()->query->get('token');;
    $post_token = \Drupal::request()->request->get('token');;
    if (!empty($post_token) || !empty($get_token)) {
      $token = !empty($post_token) ? $post_token : $get_token;
      $data_raw = \Drupal::httpClient()
        ->get('http://ulogin.ru/token.php?token=' . $token . '&host=' . $_SERVER['HTTP_HOST']);
      $data = $data_raw->getBody()->getContents();
      if (!empty($data_raw->getStatusCode() != 200)) {
        \Drupal::logger('ulogin')->warning($data);
        drupal_set_message($data, 'error');
        throw new AccessDeniedHttpException();
      }

      $data = json_decode($data, TRUE);
      // Check for error.
      if (!empty($data['error'])) {
        \Drupal::logger('ulogin')->warning($data['error']);
        drupal_set_message($data['error'], 'error');
        throw new AccessDeniedHttpException();
      }
      // Validate that returned data contains 'network' and 'uid' keys.
      if (empty($data['network']) || empty($data['uid'])) {
        \Drupal::logger('ulogin')
          ->warning('Empty data =>' . json_encode($data));
        drupal_set_message('something is wrong, try again later', 'error');
        throw new AccessDeniedHttpException();
      }
      // Remove 'access_token' property.
      unset($data['access_token']);
    }
    else {
      drupal_set_message('no token given', 'error');
      throw new AccessDeniedHttpException();
    }

    $user = \Drupal::currentUser();
    // User is already logged in, tries to add new identity.
    if ($user->isAuthenticated()) {
      // Identity is already registered.
      if ($identity = UloginHelper::identityLoad($data)) {
        // Registered to this user.

        if ($user->id() == $identity['uid']) {
          drupal_set_message($this->t('You have already registered this identity.'));
        }
        // Registered to another user.
        else {
          drupal_set_message($this->t('This identity is registered to another user.'), 'error');
        }
        return new RedirectResponse(Url::fromRoute('user.page')->toString());
      }
      // Identity is not registered - register it to the logged in user.
      else {
        UloginHelper::identitySave($data);
        drupal_set_message($this->t('New identity added.'));
        return new RedirectResponse(Url::fromRoute('user.page')->toString());
      }
    }

    $vars = \Drupal::config('ulogin.settings')->getRawData();

    if ($identity = UloginHelper::identityLoad($data)) {
      // Check if user is blocked.
      if (UloginHelper::isUserBlockedByUid($identity['uid'])) {
        drupal_set_message($this->t('Your account has not been activated or is blocked.'), 'error');
      }
      else {
        $user = User::load($identity['uid']);
        user_login_finalize($user);
      }
    }
    // Handle duplicate email addresses.
    elseif ((array_key_exists('duplicate_emails', $vars) ? $vars['duplicate_emails'] : 1) && !empty($data['email']) && $account = user_load_by_mail($data['email'])) {
      drupal_set_message($this->t('You are trying to login with email address of another user.'), 'error');
      $ulogin = \Drupal::service('user.data')->get('ulogin', $account->id());
      if (!empty($ulogin)) {
        $providers = UloginHelper::providersList();
        drupal_set_message($this->t('If you are completely sure it is your email address, try to login through %network.',
          ['%network' => $providers[$ulogin['network']]]), 'status');
      }
      else {
        drupal_set_message($this->t('If you are completely sure it is your email address, try to login using your username and password on this site. If you don\'t remember your password - <a href="@password">request new password</a>.',
          ['@password' => Url::fromRoute('user.pass')->toString()]));
      }
    }
    else {
      global $_ulogin_data;
      $_ulogin_data = $data;

      \Drupal::service('externalauth.externalauth')
        ->loginRegister(UloginHelper::makeUsername($data), 'ulogin');
      UloginHelper::userSave($data);
    }

    return new RedirectResponse(Url::fromRoute('user.page')->toString());
  }

}
