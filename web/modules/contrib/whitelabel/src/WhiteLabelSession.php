<?php

namespace Drupal\whitelabel;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Default implementation of the white label session.
 */
class WhiteLabelSession implements WhiteLabelSessionInterface {

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Constructs a new WhiteLabelSession object.
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
  public function getWhiteLabelId($key = 'whitelabel') {
    return $this->session->get($key, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function setWhiteLabelId($whitelabel_id, $key = 'whitelabel') {
    if (empty($whitelabel_id)) {
      $this->session->remove($key);
    }
    else {
      $this->session->set($key, $whitelabel_id);
    }
  }

}
