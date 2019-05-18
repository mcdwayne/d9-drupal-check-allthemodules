<?php

namespace Drupal\clever_reach\Controller\Event;

use CleverReach\BusinessLogic\Interfaces\Proxy as ProxyInterface;
use CleverReach\BusinessLogic\Sync\RecipientSyncTask;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Queue;
use Drupal;
use Drupal\clever_reach\Component\Infrastructure\ConfigService;
use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hook handler endpoint.
 */
class CleverreachEventHandlerController {

  /**
   * Hook handle endpoint.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Symfony request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Symfony response.
   *
   * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
   * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function execute(Request $request) {
    if ($request->isMethod('GET')) {
      return $this->confirmHandler($request);
    }

    if ($request->isMethod('POST')) {
      return $this->handleEvent($request);
    }

    return new Response('', Response::HTTP_BAD_REQUEST);
  }

  /**
   * Validates webhook registration.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Symfony request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Symfony response.
   */
  private function confirmHandler(Request $request) {
    $secret = $request->get('secret');
    $response = new Response();

    if ($secret) {
      $verificationToken = $this->getConfigService()
        ->getCrEventHandlerVerificationToken();

      $response->setStatusCode(Response::HTTP_OK);
      $response->setContent($verificationToken . ' ' . $secret);
      $response->headers->set('Content-Type', 'text/html');
    }
    else {
      $response->setStatusCode(Response::HTTP_BAD_REQUEST);
    }

    return $response;
  }

  /**
   * Handles recipient subscribe or unsubscribe event.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Symfony request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Symfony response.
   *
   * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
   * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function handleEvent(Request $request) {
    $crCallToken = $request->headers->get('x-cr-calltoken');
    $body = Json::decode($request->getContent());
    $response = new Response();
    $configToken = $this->getConfigService()->getCrEventHandlerCallToken();

    if ($crCallToken === NULL) {
      $response->setStatusCode(Response::HTTP_BAD_REQUEST);
    }
    elseif ($crCallToken === $configToken) {
      if ($this->shouldHandleEvent($body)) {
        $this->setSubscription($body);
      }

      $response->setStatusCode(Response::HTTP_OK);
    }
    else {
      $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
    }

    return $response;
  }

  /**
   * Subscribes or unsubscribes user, depending on the request.
   *
   * @param array $body
   *   Body of the request.
   *
   * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
   * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function setSubscription(array $body) {
    $payload = $body['payload'];
    /** @var \CleverReach\BusinessLogic\Proxy $proxy */
    $proxy = ServiceRegister::getService(ProxyInterface::CLASS_NAME);
    $user = $proxy->getRecipient($payload['group_id'], $payload['pool_id']);
    /** @var \Drupal\user\Entity\User $targetUser */
    $targetUser = user_load_by_mail($user->getEmail());
    $value = 0;

    if ($targetUser) {
      if ($body['event'] === 'receiver.subscribed') {
        $value = 1;
      }

      $targetUser->set(ConfigService::SUBSCRIPTION_FIELD, $value);
      $targetUser->save();

      Drupal::service('user.data')->set(
        ConfigService::MODULE_NAME,
        $targetUser->id(),
        ConfigService::SUBSCRIPTION_FIELD,
        (bool) $value
      );

      /** @var \CleverReach\Infrastructure\TaskExecution\Queue $queue */
      $queue = ServiceRegister::getService(Queue::CLASS_NAME);
      $queue->enqueue(
        $this->getConfigService()->getQueueName(),
        new RecipientSyncTask([$targetUser->id()])
      );
    }
  }

  /**
   * Method that checks whether the event should be handled by this integration.
   *
   * @param array $body
   *   JSON body.
   *
   * @return bool
   *   TRUE if event should be handled.
   */
  private function shouldHandleEvent(array $body) {
    $integrationId = $this->getConfigService()->getIntegrationId();

    if (!isset($body['payload']['group_id'])
      || !isset($body['payload']['pool_id'])
      || !isset($body['event'])
    ) {
      return FALSE;
    }

    $payload = $body['payload'];
    $allowedEvents = ['receiver.subscribed', 'receiver.unsubscribed'];

    return \Drupal::moduleHandler()->moduleExists(ConfigService::MODULE_NAME)
      && $integrationId === $payload['group_id']
      && in_array($body['event'], $allowedEvents, TRUE);
  }

  /**
   * Returns configuration service.
   *
   * @return \Drupal\clever_reach\Component\Infrastructure\ConfigService
   *   CleverReach configuration service.
   */
  private function getConfigService() {
    return ServiceRegister::getService(Configuration::CLASS_NAME);
  }

}
