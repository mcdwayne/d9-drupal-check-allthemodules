<?php

namespace Drupal\open_connect\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\open_connect\Exception\OpenConnectException;
use Drupal\open_connect\Plugin\OpenConnect\ProviderManager;
use Drupal\open_connect\UncacheableTrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RedirectController extends ControllerBase implements AccessInterface {

  /**
   * A string key that will used to designate the token used by this class.
   */
  const TOKEN_KEY = 'Open connect state CSRF token';

  /**
   * Drupal\open_connect\Plugin\OpenConnect\OpenConnectClientManager definition.
   *
   * @var \Drupal\open_connect\Plugin\OpenConnect\ProviderManager
   */
  protected $pluginManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The request stack used to access request globals.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * Constructs a new RedirectController instance.
   *
   * @param \Drupal\open_connect\Plugin\OpenConnect\ProviderManager $plugin_manager
   *   The identity provider manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The csrf token.
   */
  public function __construct(
    ProviderManager $plugin_manager,
    RendererInterface $renderer,
    RequestStack $request_stack,
    CsrfTokenGenerator $csrf_token
  ) {
    $this->pluginManager = $plugin_manager;
    $this->renderer = $renderer;
    $this->requestStack = $request_stack;
    $this->csrfToken = $csrf_token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.open_connect.provider'),
      $container->get('renderer'),
      $container->get('request_stack'),
      $container->get('csrf_token')
    );
  }

  /**
   * Checks access for the authentication callback.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see \Drupal\Core\Access\CustomAccessCheck::access()
   */
  public function checkAccess(AccountInterface $account) {
    $request = $this->requestStack->getCurrentRequest();
    // Confirm anti-forgery state token. This round-trip verification helps to
    // ensure that the user, not a malicious script, is making the request.
    if ($this->csrfToken->validate($request->query->get('state', ''), self::TOKEN_KEY)) {
      // Check operation.
      $configuration = $request->getSession()->get('open_connect', []);
      $configuration += ['operation' => 'login'];
      if ($configuration['operation'] === 'login' xor $account->isAuthenticated()) {
        // 'login' for anonymous user or 'connect' for authenticated user.
        $result = AccessResult::allowed();
      }
      else {
        $result = AccessResult::forbidden($configuration['operation'] === 'login' ?
          'Only anonymous user can log in with open connect.' :
          'Ensure the user is logged in.'
        );
      }
    }
    else {
      // Invalid state parameter.
      $result = AccessResult::forbidden($request->query->has('state') ?
        "The 'state' query argument is invalid." :
        "The 'state' query argument is missing."
      );
    }

    // Uncacheable because the CSRF token is highly dynamic.
    return $result->setCacheMaxAge(0);
  }

  /**
   * Authorize by redirecting to an external url.
   *
   * @param string $open_connect_provider
   *   The provider id.
   * @param Request $request
   *   Current request object.
   *
   * @return \Drupal\open_connect\UncacheableTrustedRedirectResponse
   *   The redirect response.
   */
  public function authorize($open_connect_provider, Request $request) {
    $enabled_providers = $this->config('open_connect.settings')->get('providers');
    if (empty($enabled_providers[$open_connect_provider])) {
      throw new BadRequestHttpException('Invalid identity provider.');
    }

    // Set open_connect configuration.
    $configuration = $request->getSession()->get('open_connect', []);
    $configuration += [
      'operation' => 'login',
      'return_uri' => $request->query->get('return_uri', '/user'),
    ];
    $request->getSession()->set('open_connect', $configuration);

    /** @var \Drupal\open_connect\Plugin\OpenConnect\Provider\ProviderInterface $provider */
    $provider = $this->pluginManager->createInstance($open_connect_provider, $enabled_providers[$open_connect_provider]);
    $state = $this->csrfToken->get(RedirectController::TOKEN_KEY);

    $url = $provider->getAuthorizeUrl($state)->toString();
    // Uncacheable because the response depends on a dynamic crsf token.
    return new UncacheableTrustedRedirectResponse($url);
  }

  /**
   * Authenticate user.
   *
   * @param string $open_connect_provider
   *   The provider plugin id.
   * @param Request $request
   *   Current request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function authenticate($open_connect_provider, Request $request) {
    if (!$code = $request->query->get('code')) {
      // The URI is probably being visited outside of the login flow.
      throw new NotFoundHttpException();
    }

    $enabled_providers = $this->config('open_connect.settings')->get('providers');
    if (empty($enabled_providers[$open_connect_provider])) {
      throw new BadRequestHttpException('Invalid identity provider.');
    }

    $configuration = $request->getSession()->get('open_connect', [
      'operation' => 'login',
      'return_uri' => '/user',
    ]);
    // Delete the configuration, since it's already been consumed.
    $request->getSession()->remove('open_connect');

    /** @var \Drupal\open_connect\Plugin\OpenConnect\Provider\ProviderInterface $provider */
    $provider = $this->pluginManager->createInstance($open_connect_provider, $enabled_providers[$open_connect_provider]);

    try {
      // Authenticate a user with the code.
      $user = $provider->authenticate($code);
    }
    catch (OpenConnectException $e) {
      watchdog_exception('open_connect', $e);
      drupal_set_message($this->t('Authentication failed, @message. Please try again.', ['@message' => $e->getMessage()]), 'error');
      // Redirect to homepage on failure
      // @todo: Handle external path.
      return new RedirectResponse(Url::fromUri('internal:/')->toString());
    }

    if (!$user->isActive()) {
      throw new AccessDeniedHttpException('The user is blocked.');
    }
    if ($configuration['operation'] === 'login') {
      user_login_finalize($user);
    }
    if (UrlHelper::isExternal($configuration['return_uri'])) {
      // Uncacheable because the response is following authorize().
      return new UncacheableTrustedRedirectResponse($configuration['return_uri']);
    }
    return new RedirectResponse(Url::fromUri('internal:' . $configuration['return_uri'])->toString());
  }

}
