<?php

namespace Drupal\micro_sso;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\Core\Url;
use Drupal\Core\Access\CsrfTokenGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Component\Datetime\Time;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Middleware for the micro_site module.
 */
class MicroSsoHelper implements MicroSsoHelperInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The cache sso.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheSSO;

  /**
   * The datetime service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $dateTime;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The crsf token service.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs the micro sso helper service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The Site negotiator.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The csrf token service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   * @param \Drupal\Component\Datetime\Time $time
   *   The date time service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_sso
   *   The cache sso.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SiteNegotiatorInterface $site_negotiator, CsrfTokenGenerator $csrf_token, RequestStack $request_stack, Time $time, CacheBackendInterface $cache_sso, AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
    $this->cacheSSO = $cache_sso;
    $this->dateTime = $time;
    $this->requestStack = $request_stack;
    $this->csrfToken = $csrf_token;
    $this->configFactory = $config_factory;
    $this->negotiator = $site_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function isMaster() {
    return !$this->negotiator->getActiveSite() && !(PHP_SAPI === 'cli') && $_SERVER['HTTP_HOST'] === $this->configFactory->get('micro_site.settings')->get('public_url');
  }

  /**
   * {@inheritdoc}
   */
  public function getOrigin() {
    $request = $this->requestStack->getCurrentRequest();
    $http_origin = $request->server->get('HTTP_ORIGIN');
    if (!empty($http_origin)) {
      $url = parse_url($http_origin);
      /** @var \Drupal\micro_site\Entity\SiteInterface $site */
      $site = $this->negotiator->loadByHostname($url['host']);
      if ($site instanceof SiteInterface) {
        return $site->getSiteUrl();
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function writeToken($origin) {
    // Generate one-time-token for the user, bind to the origin and ip adress
    // the token also depends on the current session identifier.
    $request = $this->requestStack->getCurrentRequest();
    $record = [
      'ip'        => $request->getClientIp(),
      'origin'    => $origin,
      'session'   => session_id(),
      'uid' => $this->currentUser->id(),
      'validity'  => $this->getRequestTime() + 10,
    ];

    $token = $this->csrfToken->get($record['ip'] . $record['origin'] . $record['session'] . $record['uid'] . $record['validity']);
    $this->cacheSSO->set($origin . ':' . $token, $record, $record['validity']);

    // Reuse destination that was given to us through the whole process, since
    // it's probably where the user really wants to go in the end.
    $destination = $request->query->get('destination');

    $login = [
      'uri' => Url::fromUri($this->getScheme() . '://' . $origin . '/sso/login', ['absolute' => TRUE])->toString(),
      'token' => $token,
      'destination' => $destination,
    ];
    return $login;
  }

  /**
   * {@inheritdoc}
   */
  public function getScheme() {
    return $this->getRequest()->getScheme();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequest() {
    return $this->requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    return $this->dateTime->getRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentUser() {
    return $this->currentUser->getAccount();
  }

  /**
   * {@inheritdoc}
   */
  public function userIsAuthenticated() {
    return $this->currentUser->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSso() {
    return $this->cacheSSO;
  }

}
