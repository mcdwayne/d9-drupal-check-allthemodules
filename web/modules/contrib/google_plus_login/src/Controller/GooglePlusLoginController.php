<?php

namespace Drupal\google_plus_login\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\externalauth\ExternalAuth;
use Drupal\google_plus_login\Event\GooglePlusLoginEvent;
use Drupal\google_plus_login\Exception\GooglePlusLoginException;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class GooglePlusLoginController extends ControllerBase {

  const SESSION_STATE = 'google_plus_login_state';

  /**
   * @var ExternalAuth
   */
  protected $externalAuth;

  /**
   * @var EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * @var LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * @var Google
   */
  protected $provider;

  /**
   * @var UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * GoogleOauthController constructor.
   *
   * @param Google $provider
   * @param ExternalAuth $externalAuth
   * @param EventDispatcherInterface $eventDispatcher
   * @param LoggerChannelFactoryInterface $logger
   * @param UrlGeneratorInterface $urlGenerator
   *
   * @internal param ConfigFactoryInterface $configFactory
   */
  public function __construct(
    Google $provider,
    ExternalAuth $externalAuth,
    EventDispatcherInterface $eventDispatcher,
    LoggerChannelFactoryInterface $logger,
    UrlGeneratorInterface $urlGenerator
  ) {
    $this->provider = $provider;
    $this->externalAuth = $externalAuth;
    $this->eventDispatcher = $eventDispatcher;
    $this->logger = $logger;
    $this->urlGenerator = $urlGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_plus_login.provider'),
      $container->get('externalauth.externalauth'),
      $container->get('event_dispatcher'),
      $container->get('logger.factory'),
      $container->get('url_generator')
    );
  }

  /**
   * Generate the authorization URL and redirect to the Google OAuth2 Provider.
   *
   * @param Request $request
   *
   * @return TrustedRedirectResponse
   */
  public function loginAction(Request $request) {
    $request->getSession()->set(
      self::SESSION_STATE,
      $this->provider->getState()
    );

    if ($request->query->has('destination')) {
      $destination = $request->getSchemeAndHttpHost()
        . (!UrlHelper::isExternal($request->query->get('destination')) ? '/' : '')
        . $request->query->get('destination');

      $request->getSession()->set('destination', $destination);
    }

    $authorizationUrl = $this
      ->provider
      ->getAuthorizationUrl(['access_type' => 'offline']);

    return $this->trustedRedirect($authorizationUrl);
  }

  /**
   * Process the authentication request, login and/or register the user account.
   *
   * @param Request $request
   *
   * @return RedirectResponse
   * @throws GooglePlusLoginException
   */
  public function authenticateAction(Request $request) {
    try {
      $this->handleAuthenticationRequest($request);
    }
    catch (\Exception $e) {
      $this->logger->get('google_plus_login')->error($e->getMessage());

      drupal_set_message(
        $this->t('There was an error while processing your request from Google.'),
        'error'
      );
    }

    return $this->trustedRedirect($this->getLoginDestinationPath($request));
  }

  /**
   * Get the path to the front page.
   *
   * @param Request $request
   *
   * @return string The destination set in the URI, the front page path if set or the
   * The destination set in the URI, the front page path if set or the
   * absolute path of '/' if not set.
   */
  protected function getLoginDestinationPath(Request $request) {
    $destination = $request->getSession()->get('destination')
      ?: $request->getSchemeAndHttpHost();
    $request->getSession()->remove('destination');

    return Url::fromUri($destination)
      ->setUrlGenerator($this->urlGenerator)
      ->toString();
  }

  /**
   * Process the authentication request from Google.
   *
   * @param Request $request
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|\Drupal\user\UserInterface|mixed
   */
  protected function handleAuthenticationRequest(Request $request) {
    $this->validateAuthenticationRequest($request);

    $token = $this->provider->getAccessToken('authorization_code', [
      'code' => $request->query->get('code'),
    ]);

    /* @var GoogleUser $owner */
    $owner = $this->provider->getResourceOwner($token);

    $account = $this->externalAuth->loginRegister(
      $owner->getId(),
      'google_plus_login',
      ['mail' => $owner->getEmail()]
    );

    $this->eventDispatcher->dispatch(
      GooglePlusLoginEvent::NAME,
      new GooglePlusLoginEvent($account, $owner)
    );

    return $account;
  }

  /**
   * Validate the authentication request from Google.
   *
   * @param Request $request
   *
   * @throws GooglePlusLoginException
   */
  protected function validateAuthenticationRequest(Request $request) {
    if ($request->query->has('error')) {
      $message = htmlspecialchars(
        $request->query->get('error'),
        ENT_QUOTES,
        'UTF-8'
      );

      throw new GooglePlusLoginException($message);
    }

    if (!$request->query->has('code')) {
      throw new GooglePlusLoginException('No code found in query string.');
    }

    if ($request->getSession()->get(self::SESSION_STATE) !== $this->provider->getState()) {
      throw new GooglePlusLoginException('Oauth2 state does not match.');
    }
  }

  /**
   * Create an uncached TrustedRedirectResponse.
   *
   * @param string $url
   *
   * @return TrustedRedirectResponse
   */
  protected function trustedRedirect($url) {
    return (new TrustedRedirectResponse($url))
      ->addCacheableDependency(
        CacheableMetadata::createFromRenderArray([
          '#cache' => [
            'max-age' => 0,
          ],
        ])
      );
  }

}
