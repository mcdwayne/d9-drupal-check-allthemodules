<?php

namespace Drupal\hn_cache_session\EventSubscriber;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\hn\Event\HnResponseEvent;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DefaultSubscriber.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    return [
      HnResponseEvent::PRE_SEND => 'alterResponseData',
    ];

  }

  const DONT_CACHE_KEYS = ['__hn'];

  /**
   * The storage that will be used to get and set data to.
   *
   * @var \Drupal\user\SharedTempStore
   */
  private $session;

  /**
   * The session factory that is used to get and set previous requests.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  private $sessionFactory;

  /**
   * The UUID Generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  private $uuid;

  /**
   * Creates a new EventSubscriber.
   */
  public function __construct(SharedTempStoreFactory $sharedTempStoreFactory, UuidInterface $uuid) {
    $this->uuid = $uuid;
    $this->sessionFactory = $sharedTempStoreFactory;
  }

  /**
   * Alters the response data.
   *
   * @param \Drupal\hn\Event\HnResponseEvent $event
   *   The event that was dispatched.
   */
  public function alterResponseData(HnResponseEvent $event) {

    // Get or generate a user ID, where all requests are stored.
    // We don't use the PHP session because of issue #2743931 and so it is
    // easier to pass the user id when pre-rendering on the server.
    $user = \Drupal::request()->query->get('_hn_user') ?: $this->uuid->generate();
    $this->session = $this->sessionFactory->get('hn_cache_session.' . $user, $user);

    $responseData = $event->getResponseData();

    // First, parse the ?_hn_verify and transfer requests from unverified to
    // verified.
    $this->verifyRequests();

    // Remove all fields from requests that are already verified.
    $verifiedRequests = $this->getVerifiedRequests();
    foreach ($verifiedRequests as $data_key => $data_fields) {
      if (!empty($responseData['data'][$data_key])) {
        foreach ($data_fields as $field) {
          unset($responseData['data'][$data_key][$field]);
        }
        // Remove the data key if all properties are removed.
        // See issue #2918729.
        if (empty($responseData['data'][$data_key])) {
          unset($responseData['data'][$data_key]);
        }
      }
    }

    // Save the current request to the session for all next requests.
    $token = $this->saveCurrentRequest($responseData);

    // Add the user and token to the response so the client can send them with
    // their next request.
    $responseData['__hn']['request']['user'] = $user;
    $responseData['__hn']['request']['token'] = $token;

    $event->setResponseData($responseData);

  }

  /**
   * Verify all requests that are passed by the user.
   *
   * All requests are saved. To make sure the user has actually received the
   * request, it must sent all non-verified request tokens with the next
   * request. This way the tokens will be verified, and the entities that
   * were in that request aren't sent again.
   */
  private function verifyRequests() {

    // Get all the verifies provided by the user. The keys are the tokens.
    $verify = \Drupal::request()->query->get('_hn_verify');

    // Verify should be an array or string. If it isn't, don't verify anything.
    if (empty($verify) || !is_array($verify)) {
      if (is_string($verify)) {
        $verify = [$verify];
      }
      else {
        return;
      }
    }

    $verify = array_flip($verify);

    // Get all stored requests that aren't verified.
    $unverifiedRequests = $this->getUnverifiedRequests();

    // Intersect both arrays to get all requests that can be verified.
    $requestsToVerify = array_intersect_key($unverifiedRequests, $verify);

    // Only continue if there are requests to verify.
    if (empty($requestsToVerify)) {
      return;
    }

    $this->setUnverifiedRequests(array_diff_key($unverifiedRequests, $requestsToVerify));

    $verifiedRequests = $this->getVerifiedRequests();

    foreach ($requestsToVerify as $request) {
      foreach ($request as $data_key => $data) {
        if (!isset($verifiedRequests[$data_key])) {
          $verifiedRequests[$data_key] = [];
        }
        $verifiedRequests[$data_key] = array_merge($data, $verifiedRequests[$data_key] ?: []);
      }
    }

    $this->setVerifiedRequests($verifiedRequests);
  }

  /**
   * Saves the current request to the session, and returns the token.
   *
   * @param $responseData
   *
   * @return mixed
   */
  private function saveCurrentRequest($responseData) {

    /** @var \Drupal\Component\Uuid\UuidInterface $uuidService */
    $uuidService = \Drupal::service('uuid');
    $uuid = $uuidService->generate();

    $unverifiedRequest = [];
    foreach ($responseData['data'] as $key => $data) {
      $unverifiedRequest[$key] = array_values(array_diff(array_keys($data), self::DONT_CACHE_KEYS));
    }

    $this->setUnverifiedRequests($this->getUnverifiedRequests() + [$uuid => $unverifiedRequest]);

    return $uuid;
  }

  /**
   *
   */
  private function getUnverifiedRequests() {
    return $this->session->get('requests.unverified') ?: [];
  }

  /**
   *
   */
  private function setUnverifiedRequests(array $requests) {
    $this->session->set('requests.unverified', $requests);
  }

  /**
   *
   */
  private function getVerifiedRequests() {
    return $this->session->get('requests.verified') ?: [];
  }

  /**
   *
   */
  private function setVerifiedRequests(array $requests) {
    $this->session->set('requests.verified', $requests);
  }

}
