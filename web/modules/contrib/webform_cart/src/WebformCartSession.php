<?php

namespace Drupal\webform_cart;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class WebformCartSession.
 */
class WebformCartSession implements WebformCartSessionInterface {

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructs a new CartSession object.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   */
  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public function getCartIds($type = self::ACTIVE) {
    $key = $this->getSessionKey($type);
    return $this->session->get($key, []);
  }

  /**
   * {@inheritdoc}
   */
  public function addCartId($cart_id, $type = self::ACTIVE) {
    $key = $this->getSessionKey($type);
    $ids = $this->session->get($key, []);
    $ids[] = $cart_id;
    $this->session->set($key, array_unique($ids));
  }

  /**
   * {@inheritdoc}
   */
  public function hasCartId($cart_id, $type = self::ACTIVE) {
    $key = $this->getSessionKey($type);
    $ids = $this->session->get($key, []);
    return in_array($cart_id, $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCartId($cart_id, $type = self::ACTIVE) {
    $key = $this->getSessionKey($type);
    $ids = $this->session->get($key, []);
    $ids = array_diff($ids, [$cart_id]);
    if (!empty($ids)) {
      $this->session->set($key, $ids);
    }
    else {
      // Remove the empty list to allow the system to clean up empty sessions.
      $this->session->remove($key);
    }
  }

  /**
   * Gets the session key for the given cart session type.
   *
   * @param string $type
   *   The cart session type.
   *
   * @return string
   *   The session key.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the given $type is unknown.
   */
  protected function getSessionKey($type) {
    $keys = [
      self::ACTIVE => 'webform_cart_orders',
      self::COMPLETED => 'webform_cart_completed_orders',
    ];
    if (!isset($keys[$type])) {
      throw new \InvalidArgumentException(sprintf('Unknown cart session type "$s".', $type));
    }

    return $keys[$type];
  }

}
