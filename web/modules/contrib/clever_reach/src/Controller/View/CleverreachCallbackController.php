<?php

namespace Drupal\clever_reach\Controller\View;

use CleverReach\BusinessLogic\Entity\AuthInfo;
use CleverReach\BusinessLogic\Interfaces\Proxy;
use CleverReach\BusinessLogic\Sync\RefreshUserInfoTask;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\Infrastructure\TaskExecution\Queue;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use CleverReach\Infrastructure\Exceptions\BadAuthInfoException;

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
   *
   * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
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

    if ($request->query->get('refresh') !== NULL) {
      $result = $this->getAuthInfo($code, TRUE);
      $this->refreshTokens($result);
    }
    else {
      $result = $this->getAuthInfo($code, FALSE);

      try {
        $this->queueRefreshUserTask($result);
      }
      catch (QueueStorageUnavailableException $e) {
        return new JsonResponse(
          [
            'status' => FALSE,
            'message' => $e->getMessage(),
          ]
              );
      }
    }

    return [
      '#theme' => self::TEMPLATE,
    ];
  }

  /**
   * Gets CleverReach auth info.
   *
   * @param string $code
   *   Code retrieved from CleverReach.
   * @param bool $refreshTokens
   *   Whether this method is invoked in context of refreshing tokens or not.
   *
   * @return mixed
   *   Authentication information object or JSON response in case of exception.
   *
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   */
  private function getAuthInfo($code, $refreshTokens = FALSE) {
    /** @var \CleverReach\BusinessLogic\Proxy $proxy */
    $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);

    try {
      $result = $proxy->getAuthInfo(
        $code,
        $this->getControllerUrl(
          'callback',
          $refreshTokens ? ['refresh' => TRUE] : []
        )
      );
    }
    catch (BadAuthInfoException $e) {
      return new JsonResponse(
        [
          'status' => FALSE,
          'message' => $e->getMessage(),
        ]
          );
    }

    return $result;
  }

  /**
   * Enqueues refresh user task.
   *
   * @param \CleverReach\BusinessLogic\Entity\AuthInfo $authInfo
   *   CleverReach auth info object.
   *
   * @throws QueueStorageUnavailableException
   */
  private function queueRefreshUserTask(AuthInfo $authInfo) {
    /** @var \CleverReach\Infrastructure\TaskExecution\Queue $queue */
    $queue = ServiceRegister::getService(Queue::CLASS_NAME);
    /** @var \Drupal\clever_reach\Component\Infrastructure\ConfigService $configService */
    $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
    $configService->setAccessToken($authInfo->getAccessToken());
    $queue->enqueue(
      $configService->getQueueName(),
      new RefreshUserInfoTask($authInfo)
    );
  }

  /**
   * Refreshes user access tokens.
   *
   * @param \CleverReach\BusinessLogic\Entity\AuthInfo $authInfo
   *   User auth info object.
   *
   * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
   */
  private function refreshTokens(AuthInfo $authInfo) {
    /** @var \Drupal\clever_reach\Component\Infrastructure\ConfigService $configService */
    $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
    /** @var \CleverReach\BusinessLogic\Proxy $proxy */
    $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);

    $userInfo = $proxy->getUserInfo($authInfo->getAccessToken());
    $localInfo = $configService->getUserInfo();

    if (isset($userInfo['id']) && $userInfo['id'] === $localInfo['id']) {
      $configService->setAccessToken($authInfo->getAccessToken());
      $configService->setRefreshToken($authInfo->getRefreshToken());
      $configService->setAccessTokenExpirationTime($authInfo->getAccessTokenDuration());
    }
  }

}
