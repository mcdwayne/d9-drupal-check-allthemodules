<?php

/**
 * @file
 * Contains \Drupal\ip_consumer_auth\Authentication\Provider\IPConsumerAuth.
 */

namespace Drupal\ip_consumer_auth\Authentication\Provider;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * IP consumer authentication provider.
 */
class IPConsumerAuth implements AuthenticationProviderInterface {

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an IP consumer authentication provider object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $config_factory->get('ip_consumer_auth.settings');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    // Only apply this validation if request has a valid accept value.
    $formats = $this->config->get('format');
    foreach ($formats as $format) {
      if (strstr($request->headers->get('Accept'), $format)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    $ip_consumers = $this->config->get('ip_consumers');
    // Determine if list of IP is a white list or black list.
    $type = $this->config->get('list_type');
    $ips = array_map('trim', explode("\n", $ip_consumers));
    $consumer_ip = $request->getClientIp(TRUE);

    // White list logic.
    if ($type) {
      if (in_array($consumer_ip, $ips)) {
        // Return Anonymous user.
        return User::getAnonymousUser();
      }
      else {
        throw new AccessDeniedHttpException();
      }
    }
    // Black list logic.
    else {
      if (!in_array($consumer_ip, $ips)) {
        // Return Anonymous user.
        return User::getAnonymousUser();
      }
      else {
        throw new AccessDeniedHttpException();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(Request $request) {}

  /**
   * {@inheritdoc}
   */
  public function handleException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof AccessDeniedHttpException) {
      $event->setException(new UnauthorizedHttpException('Invalid consumer origin.', $exception));
      return TRUE;
    }
    return FALSE;
  }

}
