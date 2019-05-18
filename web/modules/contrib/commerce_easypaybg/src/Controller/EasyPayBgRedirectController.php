<?php

namespace Drupal\commerce_easypaybg\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This is a dummy controller for mocking an off-site gateway.
 */
class EasyPayBgRedirectController implements ContainerInjectionInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructs a new EasyPayBGRedirectController object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Callback method which reacts to GET from a 302 redirect.
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  public function on302() {
    $cancel = $this->currentRequest->query->get('cancel');
    $return = $this->currentRequest->query->get('return');
    $easypay_code = $this->currentRequest->query->get('easypay_code');

    if ($easypay_code) {
      return new TrustedRedirectResponse($return);
    }

    return new TrustedRedirectResponse($cancel);
  }

}
