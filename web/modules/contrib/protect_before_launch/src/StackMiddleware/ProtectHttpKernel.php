<?php

namespace Drupal\protect_before_launch\StackMiddleware;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Password\PasswordInterface;
use Drupal\protect_before_launch\Configuration;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * A HttpKernel implementation that can protect by requiring http basic authentication.
 */
class ProtectHttpKernel implements HttpKernelInterface {
  /**
   * Original http kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Configuration.
   *
   * @var \Drupal\protect_before_launch\Configuration
   */
  protected $config;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Password checker.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $passwordChecker;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   Original http kernel.
   * @param \Drupal\protect_before_launch\Configuration $config
   *   Configuration.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $killSwitch
   *   Kill switch service.
   * @param \Drupal\Core\Password\PasswordInterface $passwordChecker
   *   Password checker service.
   */
  public function __construct(HttpKernelInterface $httpKernel, Configuration $config, EntityTypeManagerInterface $entityTypeManager, KillSwitch $killSwitch, PasswordInterface $passwordChecker) {
    $this->httpKernel = $httpKernel;
    $this->config = $config;
    $this->entityTypeManager = $entityTypeManager;
    $this->killSwitch = $killSwitch;
    $this->passwordChecker = $passwordChecker;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    if ($type === static::MASTER_REQUEST
      && $this->shouldProtect()
      && !$this->isExcluded($request)
      && !$this->authenticate($request)
    ) {
      $this->killSwitch->trigger();
      $response = $this->createDenyResponse();
    }
    else {
      $response = $this->httpKernel->handle($request, $type, $catch);
    }

    return $response;
  }

  /**
   * Create a response which informs a client to use basic authentication.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Symfony Response.
   */
  protected function createDenyResponse() {
    $response = new Response($this->config->getContent());
    $response->headers->add(['WWW-Authenticate' => sprintf('Basic realm="%s"', $this->config->getRealm())]);
    $response->setStatusCode(Response::HTTP_UNAUTHORIZED, Response::$statusTexts[Response::HTTP_UNAUTHORIZED]);

    return $response;
  }

  /**
   * Verify if request should be protected.
   *
   * @return bool
   *   Protect from accessing bool.
   */
  protected function shouldProtect() {
    switch ($this->config->getProtect()) {
      case Configuration::PROTECT_ENABLED:
        return TRUE;

      case Configuration::PROTECT_ENV_ENABLED:
        return $this->getProtectFromEnvironment();

    }
    return FALSE;
  }

  /**
   * Check if to auto enable based on env variable.
   *
   * @return bool
   *   Protection status
   */
  protected function getProtectFromEnvironment() {
    if (FALSE !== getenv($this->config->getEnvironmentKey())) {
      if (!empty($this->config->getEnvironmentValue())) {
        return getenv($this->config->getEnvironmentKey()) === $this->config->getEnvironmentValue();
      }
      else {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Authenticate username and password on configured authentication provider.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return bool
   *   Authentication status.
   */
  protected function authenticate(Request $request) {
    if (empty($request->getUser()) || empty($request->getPassword())) {
      return FALSE;
    }
    if (Configuration::AUTH_SIMPLE == $this->config->getAuthenticationType()) {
      return $this->authenticateCredentialsWithConfig($request->getUser(), $request->getPassword());
    }
    else {
      return $this->authenticateCredentialsWithDrupal($request->getUser(), $request->getPassword());
    }
  }

  /**
   * Authenticate user and password against simple username and password.
   *
   * @param string $username
   *   The username.
   * @param string $password
   *   The password.
   *
   * @return bool
   *   Return status.
   */
  protected function authenticateCredentialsWithConfig($username, $password) {
    return $this->config->validateCredentials($username, $password);
  }

  /**
   * Authenticate username and password against drupal user database.
   *
   * @param string $username
   *   The username.
   * @param string $password
   *   The password.
   *
   * @return bool
   *   Return status.
   */
  protected function authenticateCredentialsWithDrupal($username, $password) {
    try {
      return $this->passwordChecker->check($password, $this->getUserByName($username)->getPassword());
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Load a user by username.
   *
   * @param string $username
   *   Username.
   *
   * @return \Drupal\user\UserInterface
   *   Drupal User.
   *
   * @throws \Exception
   */
  protected function getUserByName($username) {
    $users = $this->entityTypeManager
      ->getStorage('user')
      ->loadByProperties(['name' => $username]);

    if (count($users) < 1) {
      throw new \Exception('User not found');
    }

    if (!$users[0] instanceof UserInterface) {
      throw new \Exception('User not found');
    }

    return $users[0];
  }

  /**
   * Check if path is excluded from password protection.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Protected function excludedPath request.
   *
   * @return bool
   *   Protected excludedPath bool.
   */
  protected function isExcluded(Request $request) {
    $currentPath = urldecode($request->getPathInfo());
    foreach ($this->config->getExcludePaths() as $path) {
      if (preg_match(sprintf('/%s/i', str_replace('/', '\/', $path)), $currentPath)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
