<?php

namespace Drupal\linkedin_oauth\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\PrivateTempStoreFactory;
use LinkedIn\LinkedIn;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class LinkedinOAuthController.
 *
 * @package Drupal\linkedin_oauth\Controller
 */
class LinkedinOAuthController extends ControllerBase {

  protected $tempStore;

  /**
   * LinkedinOAuthController constructor.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   Temporary store.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStore = $temp_store_factory->get('linkedin_oauth');
  }

  /**
   * Uses Symfony's ContainerInterface to declare dependency to be passed to constructor.
   *
   * @param ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore')
    );
  }

  /**
   * Get LinkedIn configuration object
   *
   * @result LinkedIn
   */
  private function getLinkedinObject() {
    $config = \Drupal::config('linkedin_oauth.settings');
    $url = Url::fromUri('internal:/user/linkedin-oauth/return', array('absolute' => TRUE))->toString(TRUE);
    return new LinkedIn(
      array(
        'api_key' => $config->get('api_key'),
        'api_secret' => $config->get('api_secret'),
        'callback_url' => $url->getGeneratedUrl(),
      )
    );
  }

  /**
   * Redirect to LinkedIn.
   */
  public function redirectToLn() {
    $linkedin = $this->getLinkedinObject();
    $login_url = $linkedin->getLoginUrl(
      array(
        LinkedIn::SCOPE_BASIC_PROFILE,
        LinkedIn::SCOPE_EMAIL_ADDRESS,
      )
    );
    return new TrustedRedirectResponse($login_url);
  }

  /**
   * Return from LinkedIn.
   */
  public function returnFromLn() {
    $linkedin = $this->getLinkedinObject();
    $code = $_REQUEST['code'];

    // If request is canceled there is no authorization code.
    if (!is_null($code)) {
      $token = $linkedin->getAccessToken($code);
    }

    if ($token) {
      $userinfo_fields = array(
        'id',
        'first-name',
        'last-name',
        'formatted-name',
        'email-address',
        'location',
      );
      // hook_linkedin_oauth_userinfo_fields($userinfo_fields);
      \Drupal::moduleHandler()->alter('linkedin_oauth_userinfo_fields', $userinfo_fields);
      $userinfo = $linkedin->get('/people/~:(' . implode(',', $userinfo_fields) . ')');
      // Save OAUth token to session.
      $this->tempStore->set('oauth_token', $token);
      $user = user_load_by_mail($userinfo['emailAddress']);
      if (!$user) {
        try {
          $user = User::create(array(
            'name' => $userinfo['formattedName'],
            'mail' => $userinfo['emailAddress'],
            'status' => 1,
          ));
          // hook_linkedin_oauth_create_user_alter($user, $userinfo);
          \Drupal::moduleHandler()->alter('linkedin_oauth_create_user', $user, $userinfo);
          $user->save();
        }
        catch (\Exception $e) {
          return new RedirectResponse('/');
        }
      }
      user_login_finalize($user);
    }
    $config = \Drupal::config('linkedin_oauth.settings');
    if (empty($redirect_path = $config->get('redirect_path'))) {
      $redirect_path = \Drupal::config('system.site')->get('page.front');
    }
    $url = Url::fromUri('internal:' . $redirect_path, array('absolute' => TRUE))->toString();
    return new RedirectResponse($url);
  }

}
