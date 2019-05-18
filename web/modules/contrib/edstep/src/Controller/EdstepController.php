<?php

namespace Drupal\edstep\Controller;
use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EdstepController extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   */
  public function oauthReturn() {
    $client = \Drupal::service('edstep.edstep')->getClient();
    $provider = $client->getProvider();

    $tempstore = \Drupal::service('user.private_tempstore')->get('edstep');
    $state = $tempstore->get('auth_state');

    if(!$provider->checkState($state)) {
      throw new AccessDeniedHttpException();
    }

    try {
      $access_token = $client->fetchAccessToken()->getAccessToken();
      $store = \Drupal::service('edstep.edstep')->storeAccessToken($access_token);
    }
    catch (IdentityProviderException $e) {
      \Drupal::logger('edstep')->error('Could not authenticate user %uid', [
        '%uid' => \Drupal::currentUser()->id(),
      ]);
    }

    return new RedirectResponse(Url::fromUri($state['destination'])->toString());
  }

}
