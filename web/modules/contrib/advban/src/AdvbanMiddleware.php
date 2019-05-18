<?php

namespace Drupal\advban;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides a HTTP middleware to implement IP based banning.
 */
class AdvbanMiddleware implements HttpKernelInterface {

  /**
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The ban IP manager.
   *
   * @var \Drupal\advban\AdvbanIpManagerInterface
   */
  protected $banIpManager;

  /**
   * Constructs a AdvbanMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\advban\AdvbanIpManagerInterface $manager
   *   The ban IP manager.
   */
  public function __construct(HttpKernelInterface $http_kernel, AdvbanIpManagerInterface $manager) {
    $this->httpKernel = $http_kernel;
    $this->banIpManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    $ip = $request->getClientIp();
    $ban_result = $this->banIpManager->isBanned($ip, [
      'expiry_check' => TRUE,
      'info_output' => TRUE,
    ]);
    if ($ban_result['is_banned']) {
      $ban_text = $this->banIpManager->banText([
        'ip' => $ip,
        'expiry_date' => $ban_result['expiry_date'],
      ]);
      return new Response($ban_text, 403);
    }
    return $this->httpKernel->handle($request, $type, $catch);
  }

}
