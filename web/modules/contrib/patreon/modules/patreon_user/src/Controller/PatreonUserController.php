<?php

namespace Drupal\patreon_user\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class PatreonUserController.
 *
 * @package Drupal\patreon_user\Controller
 */
class PatreonUserController extends ControllerBase {

  /**
   * Logs user in from Patreon Oauth redirect return.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects user to /user or 404s.
   */
  public function oauth() {
    $config = \Drupal::config('patreon_user.settings');
    $settings = $config->get('patreon_user_registration');

    if ($settings != PATREON_USER_NO_LOGIN) {
      if (\Drupal::currentUser()->isAnonymous() && $code = $_GET['code']) {
        $user_service = \Drupal::service('patreon_user.api');

        try {
          if ($tokens = $user_service->tokensFromCode($code)) {
            $token = (isset($tokens['access_token'])) ? $tokens['access_token'] : NULL;

            if ($token) {
              $user_service->bridge->setToken($token);

              if ($patreon_data = $user_service->fetchUser()) {
                if ($patreon_account = $user_service->bridge->getValuebyKey($patreon_data, 'data')) {
                  if ($patreon_id = $user_service->bridge->getValueByKey($patreon_account, 'id')) {
                    if ($user_service->canLogin($patreon_data)) {
                      if ($account = $user_service->getUser($patreon_account)) {
                        $user_service->storeTokens($tokens, $account);

                        if (!user_is_blocked($account->getAccountName())) {
                          $login_method = $config->get('patreon_user_login_method');

                          if ($login_method == PATREON_USER_SINGLE_SIGN_ON) {
                            user_login_finalize($account);
                            return $this->redirect('<front>');
                          }
                          else {
                            $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
                            $mail = _user_mail_notify('password_reset', $account, $langcode);
                            if (!empty($mail)) {
                              drupal_set_message($this->t('Further instructions have been sent to your email address.'));
                            }
                          }
                        }
                        else {
                          $user_config = \Drupal::config('user.settings');
                          if ($user_config->get('verify_mail') && $account->isNew()) {
                            drupal_set_message($this->t('Further instructions have been sent to your email address.'), 'error');
                          }
                          else {
                            drupal_set_message($this->t('Your account is blocked. Please contact an administrator.'), 'error');
                          }
                        }
                      }
                      else {
                        drupal_set_message($this->t('There was a problem creating your account. Please contact an administrator.'), 'error');
                      }
                    }
                    else {
                      $message = ($settings == PATREON_USER_ONLY_PATRONS) ? $this->t('Only patrons may log in via Patreon.') : $this->t('Log on via Patreon is not enabled at present.');
                      $message .= ' ' . $this->t('Please contact an administrator if you feel this is in error.');
                      drupal_set_message($message, 'error');
                    }
                  }
                }
              }
            }
          }
        }
        catch (\Exception $e) {
          $message = $this->t('The Patreon API returned the following error: :error', array(
            ':error' => $e->getMessage(),
          ));
          \Drupal::logger('patreon_user')->error($message);
          drupal_set_message($message, 'error');
        }

        return $this->redirect('<front>');
      }
    }

    throw new NotFoundHttpException();
  }

}
