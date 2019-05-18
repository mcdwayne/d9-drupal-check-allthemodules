<?php

namespace Drupal\cleverreach\Controller\View;

use CleverReach\BusinessLogic\Interfaces\Proxy;
use CleverReach\BusinessLogic\Sync\RefreshUserInfoTask;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\Infrastructure\TaskExecution\Queue;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Callback View Controller.
 *
 * @see template file cleverreach-welcome.html.twig
 */
class CleverreachCallbackController extends CleverreachResolveStateController {
  const TEMPLATE = 'cleverreach_callback';

  /**
   * Callback for the cleverreach.cleverreach.callback route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|array
   *   Returns error in JSON format if something goes wrong with
   *   connecting to CleverReach, otherwise returns view.
   */
  public function connect(Request $request) {
    if (!$code = $request->query->get('code')) {
      return new JsonResponse(
        [
          'status' => FALSE,
          'message' => t('Wrong parameters. Code not set.'),
        ]
      );
    }

    $result = $this->getAccessToken($code);
    if (isset($result['error']) || empty($result['access_token'])) {
      return new JsonResponse(
        [
          'status' => isset($result['error']) ? $result['error'] : FALSE,
          'message' => isset($result['error_description'])
          ? $result['error_description']
          : t('Unsuccessful connection.'),
        ]
      );
    }

    try {
      $this->queueRefreshUserTask($result['access_token']);
    }
    catch (QueueStorageUnavailableException $e) {
      return new JsonResponse(
        [
          'status' => FALSE,
          'message' => $e->getMessage(),
        ]
        );
    }

    return [
      '#theme' => self::TEMPLATE,
    ];
  }

  /**
   * Gets CleverReach access token.
   *
   * @param string $code
   *   Code retrieved from CleverReach.
   *
   * @return array
   *   Access token array.
   */
  private function getAccessToken($code) {
    /** @var \CleverReach\BusinessLogic\Proxy $proxy */
    $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);

    return $proxy->getAccessToken($code, $this->getControllerUrl('callback'));
  }

  /**
   * Enqueues refresh user task.
   *
   * @param string $accessToken
   *   CleverReach access token.
   */
  private function queueRefreshUserTask($accessToken) {
    /** @var \CleverReach\Infrastructure\TaskExecution\Queue $queue */
    $queue = ServiceRegister::getService(Queue::CLASS_NAME);
    $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
    $queue->enqueue($configService->getQueueName(), new RefreshUserInfoTask($accessToken));
  }

}
