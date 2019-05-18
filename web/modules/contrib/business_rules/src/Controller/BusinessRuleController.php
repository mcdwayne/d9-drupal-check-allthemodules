<?php

namespace Drupal\business_rules\Controller;

use Drupal\business_rules\Entity\BusinessRule;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class BusinessRuleController.
 */
class BusinessRuleController extends ControllerBase {

  /**
   * Disables a BusinessRule object.
   *
   * @param \Drupal\business_rules\Entity\BusinessRule $business_rule
   *   The BusinessRule object to disable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the config_test listing page.
   */
  public function disable(BusinessRule $business_rule) {
    $business_rule->disable()->save();

    return new RedirectResponse($business_rule->url('collection', ['absolute' => TRUE]));
  }

  /**
   * Enables a BusinessRule object.
   *
   * @param \Drupal\business_rules\Entity\BusinessRule $business_rule
   *   The BusinessRule object to disable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the config_test listing page.
   */
  public function enable(BusinessRule $business_rule) {
    $business_rule->enable()->save();

    return new RedirectResponse($business_rule->url('collection', ['absolute' => TRUE]));
  }

}
