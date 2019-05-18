<?php

namespace Drupal\redirecton403\EventSubscriber;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Class RedirectOn403Subscriber.
 */
class RedirectOn403Subscriber extends HttpExceptionSubscriberBase {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Drupal\Core\Session\AccountInterface definition.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The session object.
   *
   * We will use this to store information that the user submits, so that it
   * persists across requests.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The cache tag invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagInvalidator;

  /**
   * Constructs a new RedirectOn403Subscriber object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   User service mod.
   *   The user account service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Used for accessing Drupal configuration.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session object.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $invalidator
   *   The cache tag invalidator service.
   */
  public function __construct(AccountInterface $current_user,
                              LoggerChannelFactoryInterface $logger_factory,
                              ConfigFactoryInterface $config_factory,
                              SessionInterface $session,
                              CacheTagsInvalidatorInterface $invalidator) {
    $this->currentUser = $current_user;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
    $this->session = $session;
    $this->cacheTagInvalidator = $invalidator;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Handles a 403 error for HTML.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function on403(GetResponseForExceptionEvent $event) {
    $login_endpoint_type = $this->configFactory->get('redirecton403.redirect403adminsettings')->get('url_item_type');
    $config_url = ($this->configFactory->get('redirecton403.redirect403adminsettings')->get('url_item_type') == 'opt_internal') ? $this->configFactory->get('redirecton403.redirect403adminsettings')->get('internal_route') : $this->configFactory->get('redirecton403.redirect403adminsettings')->get('external_url');

    $request = $event->getRequest();
    $is_anonymous = $this->currentUser->isAnonymous();
    $route_name = $request->attributes->get('_route');
    $is_not_login = $route_name != 'user.login';

    if ($is_anonymous && $is_not_login) {
      $query = $request->query->all();
      $query['destination'] = Url::fromRoute('<current>')->toString();
      if ($login_endpoint_type == 'opt_external') {
        $this->setSessionValue('redirecton403.destination', $query['destination']);
        // Invalidate the cache tag for this session.
        $this->invalidateCacheTag();
        $login_uri = $this->configFactory->get('redirecton403.redirect403adminsettings')->get('external_url');
        $returnResponse = new TrustedRedirectResponse($login_uri);
      }
      else {
        $login_uri = Url::fromRoute('user.login', [], ['query' => $query])
          ->toString();
        $returnResponse = new RedirectResponse($login_uri);
      }
      // die( $login_uri );.
      $event->setResponse($returnResponse);
    }
  }

  /**
   * Store destination value in the session.
   *
   * External login redirect comes as token. So we
   * need to have this in session to use after token
   * is successfully retrieved.
   *
   * @param string $key
   *   The key.
   * @param string $value
   *   The value.
   */
  protected function setSessionValue($key, $value) {
    if (empty($value)) {
      // If the value is an empty string, remove the key from the session.
      $this->session->remove($key);
    }
    else {
      $this->session->set($key, $value);
    }
  }

  /**
   * Invalidate the cache tag for this session.
   *
   * Method to invalidate the cache tag when the user
   * has opted for external login to say oAuth2 endpoint.
   */
  protected function invalidateCacheTag() {
    $this->cacheTagInvalidator->invalidateTags(['redirecton403:' . $this->session->getId()]);
  }

}
