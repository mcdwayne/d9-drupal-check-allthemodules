<?php

namespace Drupal\ga_push\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * Base class to ga push rule actions.
 */
abstract class Base extends RulesActionBase {

  /**
   * Get ga push method type.
   *
   * @return string
   *   Ga push method (analytics, datalayer, etc).
   */
  public function getMethod() {
    // Retrieve selected method or the default one.
    $method = $this->getContextValue('method');
    if (empty($method)) {
      $method = $this->getDefaultMethod();
    }
    return $method;
  }

  /**
   * Return default method.
   *
   * @return string
   *   Defaut method.
   */
  public function getDefaultMethod() {
    return \Drupal::config('ga_push.settings')->get('default_method');
  }

}
