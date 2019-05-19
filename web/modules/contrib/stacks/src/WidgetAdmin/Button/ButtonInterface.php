<?php

namespace Drupal\stacks\WidgetAdmin\Button;

/**
 * Interface ButtonInterface.
 * @package Drupal\stacks\WidgetAdmin\Button
 */
interface ButtonInterface {

  /**
   * @returns button key.
   */
  public function getKey();

  /**
   * @returns renderable array.
   */
  public function build();

  /**
   * @returns if button has to be ajaxified.
   */
  public function ajaxify();

  /**
   * @return overrides default submit handler.
   */
  public function getSubmitHandler();

}
