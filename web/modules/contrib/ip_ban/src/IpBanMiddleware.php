<?php

namespace Drupal\ip_ban;

use Drupal\Component\Utility\SafeMarkup;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a HTTP middleware to implement IP based banning.
 */
class IpBanMiddleware implements HttpKernelInterface {
 
  /**
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;
  
  /**
   * The IP ban manager.
   *
   * @var \Drupal\ip_ban\IpBanSetBanInterface
   */
  protected $iPBanManager;
  
  /**
   * Constructs a BanMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\ban\IpBanSetBanInterface $manager
   *   The IP Ban manager.
   */
  public function __construct(HttpKernelInterface $http_kernel, IpBanSetBanInterface $manager) {
    $this->httpKernel = $http_kernel;
    $this->iPBanManager = $manager;
  }
  
  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    $this->iPBanManager->iPBanSetValue();
    $this->iPBanManager->iPBanDetermineAction();
    return $this->httpKernel->handle($request, $type, $catch);
  }
  
}