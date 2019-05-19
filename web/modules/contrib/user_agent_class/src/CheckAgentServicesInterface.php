<?php

namespace Drupal\user_agent_class;

/**
 * Interface CheckAgentServicesInterface.
 */
interface CheckAgentServicesInterface {

  /**
   * Return classes.
   *
   * @param string $userAgent
   *   Start date.
   *
   * @return string
   *   Return string with classes for body
   */
  public function checkUserAgent($userAgent);

}
