<?php

namespace Drupal\google_one_tap\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\user\Controller\UserAuthenticationController as UserController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;
use Egulias\EmailValidator\EmailValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * Returns responses for Google One Tap routes.
 */
class UserAuthenticationController extends UserController {

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * Constructs a UserAuthenticationController.
   *
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood controller.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   */
  public function __construct(
    FloodInterface $flood,
    UserStorageInterface $user_storage,
    CsrfTokenGenerator $csrf_token,
    UserAuthInterface $user_auth,
    RouteProviderInterface $route_provider,
    Serializer $serializer,
    array $serializer_formats,
    LoggerInterface $logger,
    EmailValidator $email_validator
  ) {
    parent::__construct($flood, $user_storage, $csrf_token, $user_auth, $route_provider, $serializer, $serializer_formats, $logger);
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    if ($container->hasParameter('serializer.formats') && $container->has('serializer')) {
      $serializer = $container->get('serializer');
      $formats = $container->getParameter('serializer.formats');
    }
    else {
      $formats = ['json'];
      $encoders = [new JsonEncoder()];
      $serializer = new Serializer([], $encoders);
    }

    return new static(
      $container->get('flood'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('csrf_token'),
      $container->get('user.auth'),
      $container->get('router.route_provider'),
      $serializer,
      $formats,
      $container->get('logger.factory')->get('user'),
      $container->get('email.validator')
    );
  }

  /**
   * Handles sign-up/sign-in via Google One Tap.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function login(Request $request) {

    // Get token.
    if (!$token = $request->request->get('idToken')) {
      throw new BadRequestHttpException('Missing idToken');
    }

    $config = $this->config('google_one_tap.configuration');
    // Get Google Client.
    $client = new \Google_Client(['client_id' => $config->get('client_id')]);

    // Verify token.
    $payload = $client->verifyIdToken($token);
    if (!$payload) {
      throw new BadRequestHttpException('Invalid Token');
    }

    // Check if we have domain restriction enabled.
    if ($config->get('use_domain_restriction')) {
      // If domain is not the same set error.
      if (!isset($payload['hd'])) {
        drupal_set_message(t('You are not using G Suite account. Only users of domain %domain can login.', ['%domain' => $config->get('domain')]), 'error');
        return new JsonResponse('');
      }
      elseif ($payload['hd'] != $config->get('domain')) {
        drupal_set_message(t('You are not authenticated user of domain %domain.', ['%domain' => $config->get('domain')]), 'error');
        return new JsonResponse('');
      }
    }

    if (empty($payload['email']) || !$this->emailValidator->isValid($payload['email'])) {
      throw new BadRequestHttpException('Incorrect email address');
    }

    if (!$user = user_load_by_mail($payload['email'])) {
      $this
        ->getLogger('google_one_tap')
        ->notice('Registering a new user for %mail', [
          '%mail' => $payload['email'],
        ]);
      // Register the user.
      $edit = [
        'name' => $this->findNextAvailableAccountName($payload['name']),
        'mail' => $payload['email'],
        'status' => 1,
      ];
      /** @var \Drupal\user\UserInterface $new_user */
      $new_user = $this->userStorage->create($edit);
      $new_user->save();
      $user = $new_user;
    }

    if ($this->userIsBlocked($user->getEmail())) {
      throw new BadRequestHttpException('The user has not been activated or is blocked.');
    }

    $this->userLoginFinalize($user);

    // Compose basic response.
    // @see Drupal\user\Controller\UserAuthenticationController::login()
    $response_data = [];
    if ($user->get('uid')->access('view', $user)) {
      $response_data['current_user']['uid'] = $user->id();
    }
    if ($user->get('roles')->access('view', $user)) {
      $response_data['current_user']['roles'] = $user->getRoles();
    }
    if ($user->get('name')->access('view', $user)) {
      $response_data['current_user']['name'] = $user->getAccountName();
    }
    $response_data['csrf_token'] = $this->csrfToken->get('rest');

    $logout_route = $this->routeProvider->getRouteByName('user.logout.http');
    // Trim '/' off path to match \Drupal\Core\Access\CsrfAccessCheck.
    $logout_path = ltrim($logout_route->getPath(), '/');
    $response_data['logout_token'] = $this->csrfToken->get($logout_path);

    return new JsonResponse($response_data);
  }

  /**
   * Verifies if the user is blocked.
   *
   * @param string $email
   *
   * @return bool
   */
  protected function userIsBlocked($email) {
    return (bool) $this->userStorage
      ->getQuery()
      ->condition('mail', $email)
      ->condition('status', 0)
      ->execute();
  }

  /**
   * Resolves first available username.
   *
   * @param $name
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   */
  protected function findNextAvailableAccountName($name) {
    $count = 0;
    $old_name = $name;
    while ($this->accountNameIsRegistered($name)) {
      $count++;
      $name = new FormattableMarkup('@name_@count', ['@name' => $old_name, '@count' => $count]);
    };
    return $name;
  }

  /**
   * Verifies if username is already registered.
   *
   * @param string $name
   *
   * @return bool
   */
  protected function accountNameIsRegistered($name) {
    return (bool) $this->userStorage
      ->getQuery()
      ->condition('name', $name)
      ->execute();
  }
}
