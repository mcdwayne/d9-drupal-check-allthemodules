<?php

namespace Drupal\entity_http_exception\EventSubscriber;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\entity_http_exception\Utils\EntityHttpExceptionUtils as Utils;

/**
 * Unpublished Nodes On 403 Subscriber class.
 */
class UnpublishedNodesOn403Subscriber extends HttpExceptionSubscriberBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Forum settings config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(AccountProxyInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Fires redirects whenever a 403 meets the criteria for unpublished nodes.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event.
   */
  public function on403(GetResponseForExceptionEvent $event) {
    if ($node = $event->getRequest()->attributes->get('node')) {
      $node_type = $node->get('type')->getString();
      $is_published = $node->get('status')->getString();
      $config = $this->configFactory->get('entity_http_exception.settings');

      $is_anonymous = $this->currentUser->isAnonymous();
      $checked_unpublished = $config->get(Utils::getUnpublishedNodesKey($node_type));
      $http_exception_code = $config->get(Utils::getHttpExceptionCodeKey('node', $node_type));

      if ($is_published == 0 && $is_anonymous && $checked_unpublished && $http_exception_code == 404) {
        $event->setException(new NotFoundHttpException());
      }
    }
  }

}
