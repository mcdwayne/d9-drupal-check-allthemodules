<?php
/**
 * @file
 * Contains \Drupal\zendesk\Controller\ZendeskSSOController.
 */

namespace Drupal\zendesk_sso\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for zendesk routes.
 */
class ZendeskSSOController extends ControllerBase {

  /**
   * Zendesk Manager Service.
   *
   * @var \Drupal\zendesk\ZendeskManager
   */
  protected $zendeskManager;

  /**
   * Injects ZendeskManager Service.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('zendesk.manager'));
  }

  /**
   * Remote authentication script.
   *
   * @see http://developer.zendesk.com/documentation/sso
   * @see https://support.zendesk.com/entries/23675367
   */
  public function sso() {
    // Check if anonymous, if so redirect to login with destination the path where
    // he comes from.
    $account = \Drupal::currentUser();
    if ($account->id()) {
      // Check if user role is allowed to be authenticated.
      if (zendesk_user_has_access($account)) {
        $account = \Drupal::currentUser();

        $token = array(
          'jti' => sha1($account->id() . REQUEST_TIME . rand()),
          'iat' => REQUEST_TIME,
          'name' => $account->getUsername(),
          'email' => $account->getEmail(),
          'external_id' => $account->id(),
        );
        $key = \Drupal::config('zendesk.settings')
          ->get('zendesk_jwt_shared_secret');
        $jwt = zendesk_jwt_encode($token, $key);

        // Redirect
        $url = \Drupal::config('zendesk.settings')->get('zendesk_url') . '/access/jwt';

        return new RedirectResponse(url($url, array('query' => array('jwt' => $jwt))));
      }
      else {
        return new RedirectResponse(url(Drupal::config('zendesk.settings')
          ->get('zendesk_no_permission_page')));
      }
    }
    else {
      return new RedirectResponse(url('user', array('query' => array('destination' => 'services/zendesk'))));
    }
  }

}
