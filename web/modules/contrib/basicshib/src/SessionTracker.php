<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/15/17
 * Time: 11:51 AM
 */

namespace Drupal\basicshib;


use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class SessionTracker
 *
 * Tracks the shibboleth session id that was used for login.
 *
 * @package Drupal\basicshib
 */
class SessionTracker {
  const VARNAME = 'basicshib_tracked_session_id';

  /**
   * @var SessionInterface
   */
  private $session;

  /**
   * SessionTracker constructor.
   *
   * @param SessionInterface $session
   */
  public function __construct(SessionInterface $session = null) {
    $this->session = $session;
  }

  /**
   * Get the session id.
   *
   * @return string|null
   *   The tracked session id.
   */
  public function get() {
    return $this->session !== null
      ? $this->session->get(self::VARNAME)
      : null;
  }

  /**
   * Set the session id.
   *
   * @param string|null $value
   *   The value to set, or NULL to remove the session entry.
   */
  public function set($value) {
    if ($this->session !== null) {
      $this->session->set(self::VARNAME, $value);
    }
  }

  /**
   * Clear the tracked session id.
   */
  public function clear() {
    if ($this->session) {
      $this->session->remove(self::VARNAME);
    }
  }

  /**
   * @return bool
   */
  public function exists() {
    if ($this->session) {
      return $this->session->has(self::VARNAME);
    }
  }
}

