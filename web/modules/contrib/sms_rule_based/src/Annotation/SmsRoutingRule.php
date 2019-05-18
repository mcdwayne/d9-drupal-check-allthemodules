<?php

namespace Drupal\sms_rule_based\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines SmsRoutingRule plugin annotation object.
 *
 * @Annotation
 */
class SmsRoutingRule extends Plugin {

  /**
   * The machine name of the SMS routing rule type.
   *
   * @var string
   */
  protected $id;

  /**
   * Translated user-readable label.
   *
   * @var string
   */
  protected $label;

  /**
   * Translated user-readable description of the rule type.
   *
   * @var string
   */
  protected $description;

}
