<?php

namespace Drupal\commerce_installments\Event;

final class InstallmentPlanMethodsEvents {

  /**
   * Name of the event fired when installment plan methods are loaded for an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_installments\Event\FilterInstallmentPlanMethodsEvent
   */
  const FILTER_PLAN_METHODS = 'commerce_installments.filter_plan_methods';

}
