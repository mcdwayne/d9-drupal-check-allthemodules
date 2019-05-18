<?php

namespace Drupal\gitlab_time_tracker_users\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\TrustedRedirectResponse;


/**
 * Class GitlabAuthenticationController.
 */
class GitlabAuthenticationController extends ControllerBase {

  /**
   * Authenticate.
   *
   * @return string
   *   Return Hello string.
   */
  public function authenticate(Request $request) {
    $configuration = Settings::get('gitlab');
    \Drupal::service('page_cache_kill_switch')->trigger();
    $provider = new \Omines\OAuth2\Client\Provider\Gitlab(
      [
        'clientId'          => $configuration['client_id'],
        'clientSecret'      => $configuration['client_secret'],
        'redirectUri'       => Url::fromRoute('gitlab_time_tracker_users.gitlab_authentication_controller_authenticate', [], ['absolute' => TRUE])
          ->toString(TRUE)
          ->getGeneratedUrl(),
        'domain'            => $configuration['server'],
      ]
    );

    if (!isset($_GET['code'])) {

      // If we don't have an authorization code then get one
      $authUrl = $provider->getAuthorizationUrl();
      $request->getSession()->set('oauth2state', $provider->getState());

      return new TrustedRedirectResponse($authUrl);

      // Check given state against previously stored one to mitigate CSRF attack
    } elseif (empty($_GET['state']) || ($_GET['state'] !== $request->getSession()->get('oauth2state'))) {

      $request->getSession()->remove('oauth2state');
      return new Response($this->t('Invalid state'));

    } else {

      // Try to get an access token (using the authorization code grant)
      $token = $provider->getAccessToken(
        'authorization_code',
        [
          'code' => $_GET['code'],
        ]
      );

      // Authenticate user.
      try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        if ($account = $this->getUser($user->getId())) {
          $request->getSession()->set('gitlab_token', $token);
          user_login_finalize($account);

          return $this->redirect('entity.user.canonical', ['user' => $account->id()]);
        }
        else {
          throw new \Exception($this->t("User hasn't been found"));
        }
      } catch (Exception $e) {
        $this->messenger()->addWarning($this->t('Something went wrong during OAUTH authentication flow.'));
        return $this->redirect('<front>');
      }
    }
  }


  protected function getUser($id) {
    $query = $this->entityTypeManager()->getStorage('user')->getQuery();
    $query->condition('field_gitlab_id', $id, '=');

    $results = $query->execute();

    if (!empty($results)) {
      return $this
        ->entityTypeManager()
        ->getStorage('user')
        ->load(reset($results));
    }
    else {
      return NULL;
    }
  }

}
