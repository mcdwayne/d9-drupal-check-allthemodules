<?php

namespace Drupal\client_connection_square\Controller;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a controller for Square access token retrieval via OAuth.
 */
class OauthToken extends ControllerBase {

  /**
   * Gets the user temp-store factory.
   *
   * @return \Drupal\user\PrivateTempStoreFactory
   *   The tempstore factory instance.
   */
  protected function getTempStore() {
    return \Drupal::service('user.private_tempstore');
  }

  /**
   * Provides a route for square to redirect to when obtaining the oauth token.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The response.
   */
  public function obtain(Request $request) {
    $code = $request->query->get('code');
    $options = [
      'query' => [
        'code' => $code,
      ],
    ];

    // Get previous form uri from temp store.
    $collection = $this->getTempStore()->get('client_connection_square');
    $uri = $collection->get($request->query->get('state'));
    $collection->delete($request->query->get('state'));
    if ($uri) {
      $url = Url::fromUri($uri, $options)->toString();
    }
    else {
      $url = Url::fromRoute('client_connection.settings')->toString();
    }

    return new RedirectResponse($url);
  }

  /**
   * Controller access method.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function obtainAccess() {
    // $request is not passed in to _custom_access.
    // @see https://www.drupal.org/node/2786941
    if (\Drupal::csrfToken()->validate(\Drupal::request()->query->get('state'))) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden($this->t('Invalid token'));
  }

}
