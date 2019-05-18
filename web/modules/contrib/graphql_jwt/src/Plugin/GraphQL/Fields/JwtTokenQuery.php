<?php

namespace Drupal\graphql_jwt\Plugin\GraphQL\Fields;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\Plugin\GraphQL\Fields\FieldPluginBase;
use Drupal\jwt\Authentication\Provider\JwtAuth;
use Drupal\user\Entity\User;
use Drupal\user\UserAuthInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * A query field that returns JWT auth token for a user.
 *
 * @GraphQLField(
 *   secure = true,
 *   id = "graphql_jwt_token_query",
 *   type = "JwtTokenResult",
 *   name = "JwtToken",
 *   nullable = true,
 *   multi = false,
 *   arguments = {
 *     "username" = "String!",
 *     "password" = "String!"
 *   }
 * )
 */
class JwtTokenQuery extends FieldPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The Jwt Authentication provider.
   *
   * @var \Drupal\jwt\Authentication\Provider\JwtAuth
   */
  protected $auth;

  /**
   * The user authentication object.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The account switcher.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * The http kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The GraphQL JWT module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $graphqlJwtConfig;

  /**
   * The email registration module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $emailRegistrationConfig;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    JwtAuth $auth,
    UserAuthInterface $user_auth,
    AccountInterface $current_user,
    AccountSwitcherInterface $account_switcher,
    HttpKernelInterface $http_kernel,
    SessionManagerInterface $session_manager,
    ConfigFactoryInterface $config,
    ModuleHandlerInterface $module_handler,
    LoggerChannel $logger
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->auth = $auth;
    $this->userAuth = $user_auth;
    $this->currentUser = $current_user;
    $this->accountSwitcher = $account_switcher;
    $this->httpKernel = $http_kernel;
    $this->sessionManager = $session_manager;
    $this->graphqlJwtConfig = $config->get('graphql_jwt.config');
    $this->emailRegistrationConfig = $config->get('email_registration.settings');
    $this->moduleHandler = $module_handler;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('jwt.authentication.jwt'),
      $container->get('user.auth'),
      $container->get('current_user'),
      $container->get('account_switcher'),
      $container->get('http_kernel'),
      $container->get('session_manager'),
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('logger.channel.graphql_jwt')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function resolveValues($value, array $args, ResolveContext $context, ResolveInfo $info) {
    // First allow to login with email as email registration module does. See
    // email_registration_user_login_validate().
    if ($this->moduleHandler->moduleExists('email_registration')) {
      if ($user = user_load_by_mail($args['username'])) {
        /** @var \Drupal\user\Entity\User $user */
        $args['username'] = $user->getAccountName();
      }
      elseif (!$this->emailRegistrationConfig->get('login_with_username')) {
        return $this->logger->error($this->t('Unrecognized email address or password.'));
      }
    }

    // Continue with regular login.
    $content = [
      'name' => $args['username'],
      'pass' => $args['password'],
    ];
    $current_user = $this->currentUser->getAccount();
    // Create request against /user/login requiring JSON output.
    $request = Request::create('/user/login?_format=json', 'POST', [], [], [], [], json_encode($content));
    // Make the subrequest to authenticate user the same way as Drupal does.
    $response = $this->httpKernel->handle($request, KernelInterface::SUB_REQUEST);
    if ($json = json_decode($response->getContent(), TRUE)) {
      // If user has not been logged in, return error message.
      if (!isset($json['current_user']['uid'])) {
        return $this->logger->error($json['message']);
      }
      $uid = $json['current_user']['uid'];
      if ($uid && $user = User::load($uid)) {
        // Switch to authenticated account, since JWT token generation is based
        // on current user. See JwtAuthIssuerSubscriber::setDrupalClaims().
        $this->accountSwitcher->switchTo($user);
        $token = $this->auth->generateToken();
        // Destroy session if desired.
        if ($this->graphqlJwtConfig->get('session_destroy')) {
          $this->sessionManager->destroy();
          header_remove('Set-Cookie');
        }
        // Switch back to the original user for the rest of the request.
        $this->accountSwitcher->switchTo($current_user);
        // Return the JWT token.
        if ($token !== FALSE) {
          $return = [
            'type' => 'JwtTokenResult',
            'jwt' => $token,
          ];
          yield $return;
        }
      }
    }
    return $this->logger->error($this->t('Error logging user.'));
  }

}
