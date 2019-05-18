<?php

namespace Drupal\micro_site\StackMiddleware;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Middleware for the micro_site module.
 */
class MicroSiteMiddleware implements HttpKernelInterface {

  /**
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The Site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a BanMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The Site negotiator.
   */
  public function __construct(HttpKernelInterface $http_kernel, SiteNegotiatorInterface $site_negotiator) {
    $this->httpKernel = $http_kernel;
    $this->negotiator = $site_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    $active_site = $this->negotiator->getActiveSite();
    if (empty($active_site)) {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    $user = '';
    $pass= '';
    $shield_enabled = 0;
    if ($active_site instanceof SiteInterface) {
      $shield_enabled = $active_site->getSiteShield();
      $user = $active_site->getSiteShieldUser();
      $pass = $active_site->getSiteShieldPassword();
    }

    if ($type != self::MASTER_REQUEST || !$shield_enabled || !$user || PHP_SAPI === 'cli') {
      // Bypass:
      // 1. Subrequests
      // 2. Empty username
      // 3. CLI requests if CLI is allowed.
      return $this->httpKernel->handle($request, $type, $catch);
    }
    else {
      if ($request->server->has('PHP_AUTH_USER') && $request->server->has('PHP_AUTH_PW')) {
        $input_user = $request->server->get('PHP_AUTH_USER');
        $input_pass = $request->server->get('PHP_AUTH_PW');
      }
      elseif (!empty($request->server->get('HTTP_AUTHORIZATION'))) {
        list($input_user, $input_pass) = explode(':', base64_decode(substr($request->server->get('HTTP_AUTHORIZATION'), 6)), 2);
      }
      elseif (!empty($request->server->get('REDIRECT_HTTP_AUTHORIZATION'))) {
        list($input_user, $input_pass) = explode(':', base64_decode(substr($request->server->get('REDIRECT_HTTP_AUTHORIZATION'), 6)), 2);
      }

      if (isset($input_user) && $input_user === $user && Crypt::hashEquals($pass, $input_pass)) {
        return $this->httpKernel->handle($request, $type, $catch);
      }
    }

    $response = new Response();
    $response->headers->add([
      'WWW-Authenticate' => 'Basic realm="' . strtr('Please authenticate', [
          '[user]' => $user,
          '[pass]' => $pass,
        ]) . '"',
    ]);
    $response->setStatusCode(401);
    return $response;
  }

}
