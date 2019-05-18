<?php

namespace Drupal\basic_auth_limited\StackMiddleware;

use Drupal\Core\Config\Config;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Handles request to limit basic auth only to users matching pattern.
 */
class BasicAuthMiddleware implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;


  /**
   * The basic auth related settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * Constructs a PageCache object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\Core\Config\Config $settings
   *   The basic auth related settings.
   */
  public function __construct(HttpKernelInterface $http_kernel, Config $settings) {
    $this->httpKernel = $http_kernel;
    $this->settings = $settings->get();
  }

  /**
   * Get regex pattern to exclude users from authentication.
   *
   * @return string
   *   Regex pattern.
   */
  public function getPattern() {
    return $this->settings['basic_auth_user_regex'];
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    $username = $request->headers->get('PHP_AUTH_USER');
    if ($username && !empty($_SERVER['PHP_AUTH_USER'])) {
      $pattern = $this->getPattern();
      // If username does not match pattern then do not authenticate that user.
      if (!preg_match($pattern, $_SERVER['PHP_AUTH_USER'])) {
        // Authentication is prevented by simply unsetting PHP_AUTH_USER since
        // without the variable the basic authentication provider won't apply.
        $request->headers->set('PHP_AUTH_USER', NULL);
      }
    }
    return $this->httpKernel->handle($request, $type, $catch);
  }

}
