<?php

namespace Drupal\api_ai_webhook\Authentication\Provider;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * HTTP Basic authentication provider.
 */
class ApiAiAuth implements AuthenticationProviderInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * State Manager.
   *
   * @var \Drupal\Core\State\StateInterface $stateManager
   */
  protected $stateManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * The configured Api.AI authentication type. Either none, basic or headers.
   *
   * @var string
   */
  protected $type;

  /**
   * Constructs a HTTP basic authentication provider object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The user authentication service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, EntityTypeManagerInterface $entity_type_manager, FloodInterface $flood) {
    $this->configFactory = $config_factory;
    $this->stateManager = $state;
    $this->entityTypeManager = $entity_type_manager;
    $this->flood = $flood;

    $this->type = $this->configFactory->getEditable('api_ai_webhook.settings')->get('auth.type') ?: 'none';
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    // Apparently service current_route_match doesn't understand the root at
    // this point, so I'm afraid we need to check it manually from the request
    // path.
    return rtrim($request->getPathInfo(), '/') === '/api.ai/webhook';
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {

    $flood_config = $this->configFactory->get('user.flood');
    if ($this->flood->isAllowed('api_ai_auth.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
      $auth_method = 'authenticate' . ucfirst($this->type);
      if (method_exists($this, $auth_method)) {
        if (call_user_func([$this, $auth_method], $request)) {
          // Return Anonymous user.
          return $this->entityTypeManager->getStorage('user')->load(0);
        }
      }

      if ($this->type == 'basic') {
        $challenge = new FormattableMarkup('Basic realm="Api.AI Webhook"', []);
        throw new UnauthorizedHttpException((string) $challenge, 'No authentication credentials provided.');
      }

      $this->flood->register('api_ai_auth.failed_login_ip', $flood_config->get('ip_window'));
    }

    throw new AccessDeniedHttpException();
  }

  /**
   * Authenticates the user for 'basic' type.
   *
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   The request object.
   *
   * @return bool
   *   TRUE in case of a successful authentication, FALSE otherwise.
   */
  protected function authenticateBasic(Request $request) {
    $username = $request->headers->get('PHP_AUTH_USER');
    $password = $request->headers->get('PHP_AUTH_PW');

    if ($username === $this->configFactory->getEditable('api_ai_webhook.settings')->get('auth.values.username')) {
      $state_data = $this->stateManager->get('api_ai_webhook.auth', []);
      $password = Crypt::hmacBase64($password, Settings::getHashSalt());

      if (isset($state_data['password']) && $password === $state_data['password']) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Authenticates the user for 'headers' type.
   *
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   The request object.
   *
   * @return bool
   *   TRUE in case of a successful authentication, FALSE otherwise.
   */
  protected function authenticateHeaders(Request $request) {
    $state_data = $this->stateManager->get('api_ai_webhook.auth', []);
    $valid = FALSE;

    if (isset($state_data['headers']) && is_array($state_data['headers'])) {
      // Assume auth is already valid, as if config has an empty array that
      // means no headers check should be ran.
      $valid = TRUE;

      foreach ($state_data['headers'] as $key => $value) {
        if ($value !== $request->headers->get($key)) {
          $valid = FALSE;
          break;
        }
      }
    }

    return $valid;
  }

  /**
   * The 'None' authentication method.
   *
   * @return bool
   *   This auth method always return TRUE, and so authenticating the request.
   */
  protected function authenticateNone() {
    return TRUE;
  }

}
