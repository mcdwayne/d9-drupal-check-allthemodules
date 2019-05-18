<?php

namespace Drupal\commerce_add_to_cart_link;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Default cart link token service implementation.
 */
class CartLinkToken implements CartLinkTokenInterface {

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new CartLinkToken object.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_generator
   *   The CSRF token generator.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(CsrfTokenGenerator $csrf_token_generator, AccountInterface $current_user, RequestStack $request_stack) {
    $this->csrfTokenGenerator = $csrf_token_generator;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function generate(ProductVariationInterface $variation) {
    // Ensure that an anonymous user has a session created, as we need to
    // generate a token, which won't work without having a session.
    if ($this->currentUser->isAnonymous()) {
      $this->requestStack->getCurrentRequest()->getSession()->set('forced', TRUE);
    }

    $value = $this->generateTokenValue($variation);
    return $this->csrfTokenGenerator->get($value);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(ProductVariationInterface $variation, $token) {
    $value = $this->generateTokenValue($variation);
    return $this->csrfTokenGenerator->validate($token, $value);
  }

  /**
   * Generates the value used for the token generation.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   *
   * @return string
   *   The value used for the token generation.
   */
  protected function generateTokenValue(ProductVariationInterface $variation) {
    return sprintf('cart_link:%s:%s', $variation->getProductId(), $variation->id());
  }

}
