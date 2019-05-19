<?php

namespace Drupal\user_online_status\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * An example controller.
 */
class OnlineStatusController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content(AccountInterface $user, Request $request) {

    $last = $user->getLastAccessedTime();
    $now = $request->server->get('REQUEST_TIME');
    $status = [];

    // Credits to Kevin Quillen.
    // @see https://drupal.stackexchange.com/a/273974/15055
    switch (TRUE) {

      case ($last >= ($now - 900)):
        $status['online_status'] = 'online';
        break;

      case (($last < ($now - 900)) && $last > ($now - 1800)):
        $status['online_status'] = 'absent';
        break;

      default:
        $status['online_status'] = 'offline';
        break;
    }

    // Mark this page as being non-cacheable.
    // Oh, seems JSON response are non-cacheable by default.
    // @see https://spinningcode.org/2017/05/cached-json-responses-in-drupal-8/
    // @see Drupal\Core\Cache\CacheableJsonResponse;
    // @see Drupal\Core\Cache\CacheableMetadata;
    $response = new JsonResponse();
    $response->setData($status);
    return $response;
  }

}
