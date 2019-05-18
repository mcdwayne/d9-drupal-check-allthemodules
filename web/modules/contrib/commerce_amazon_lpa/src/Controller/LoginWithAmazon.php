<?php

namespace Drupal\commerce_amazon_lpa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Amazon login.
 */
class LoginWithAmazon extends ControllerBase {

  /**
   * Controller method.
   */
  public function handleLoginRedirect(Request $request) {
    /** @var \Drupal\commerce_amazon_lpa\AmazonPay $amazon_pay */
    $amazon_pay = \Drupal::service('commerce_amazon_lpa.amazon_pay');

    if ($request->query->has('access_token')) {
      $access_token = $request->query->get('access_token');
    }
    elseif ($request->cookies->has('amazon_Login_accessToken')) {
      $access_token = $request->cookies->get('amazon_Login_accessToken');
    }
    else {
      drupal_set_message($this->t('There was an error signing in.'), 'error');
      return new RedirectResponse(Url::fromRoute('user.login')->toString());
    }

    try {
      $user_information = $amazon_pay->getUserInfo($access_token);
      $existing = user_load_by_mail($user_information['email']);
      if ($existing) {
        // @todo create an event for existing user login.
        user_login_finalize($existing);
      }
      else {
        // @todo create an event for new user creation/login.
        $user = User::create([
          'name' => $user_information['email'],
          'mail' => $user_information['email'],
          'pass' => user_password(),
          'status' => TRUE,
        ]);
        $user->save();
        user_login_finalize($user);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('commerce_amazon_lpa')->debug(
        sprintf('%s: %s', get_class($e), $e->getMessage())
      );
      drupal_set_message($this->t('There was an error signing in.'), 'error');
      return new RedirectResponse(Url::fromRoute('user.login')->toString());
    }

    // This is legacy logic from 7.x, where sometimes the Login with Amazon
    // access token was lost.
    if ($request->cookies->has('amazon_Login_accessToken')) {
      $cookie = $request->cookies->get('amazon_Login_accessToken');
      setrawcookie('amazon_Login_accessToken', $cookie, 0, '/', '', TRUE);
    }
    return new RedirectResponse(Url::fromRoute('user.page')->toString());
  }

}
