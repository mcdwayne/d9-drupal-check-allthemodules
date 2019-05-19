<?php

namespace Drupal\stacks\WidgetAdmin\Step;

/**
 * Class StepsEnum
 * @package Drupal\stacks\WidgetAdminStep
 *
 * Define the available steps.
 */
abstract class StepsEnum {

  const STEP_ONE = 1;
  const STEP_TWO = 2;
  const STEP_FINALIZE = 6;

  public static function toArray() {
    return [
      self::STEP_ONE => 'step-one',
      self::STEP_TWO => 'step-two',
      self::STEP_FINALIZE => 'step-finalize',
    ];
  }

  public static function map($step) {
    $map = [
      self::STEP_ONE => 'Drupal\\stacks\\WidgetAdmin\\Step\\StepOne',
      self::STEP_TWO => 'Drupal\\stacks\\WidgetAdmin\\Step\\StepTwo',
      self::STEP_FINALIZE => 'Drupal\\stacks\\WidgetAdmin\\Step\\StepFinalize',
    ];

    return isset($map[$step]) ? $map[$step] : FALSE;
  }

}