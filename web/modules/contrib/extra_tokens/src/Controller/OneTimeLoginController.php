<?php
namespace Drupal\extra_tokens\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\extra_tokens\ZinaDesign\PasswordResetTokenGenerator;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class OneTimeLoginController extends ControllerBase {
  public function login(Request $request, $uidb64, $token) {
    $uid = PasswordResetTokenGenerator::urlsafe_b64decode($uidb64);
    $account = new \stdClass();
    $drupal_user = User::load($uid);
    $account->id = $drupal_user->id();
    $account->password = $drupal_user->getPassword();
    $account->last_login = '';
    if(PasswordResetTokenGenerator::check_token($account, $token) === false) {
      drupal_set_message($this->t('Link is not longer valid please use /user/login page'), 'error');
      return $this->redirect('<front>');
    }
    $next = $request->get('next');
    if(!$next) {
      $next = '/user/'.$account->id.'/orders';
    }
    else {
      $next = urldecode($next);
    }
    user_login_finalize($drupal_user);
    return new RedirectResponse($next);
  }
}