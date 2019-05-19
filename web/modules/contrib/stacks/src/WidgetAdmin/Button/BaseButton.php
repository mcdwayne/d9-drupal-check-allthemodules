<?php

namespace Drupal\stacks\WidgetAdmin\Button;

/**
 * Class BaseButton.
 * @package Drupal\stacks\WidgetAdmin\Button
 */
abstract class BaseButton implements ButtonInterface {

  /**
   * @inheritDoc.
   */
  public function ajaxify() {
    return TRUE;
  }

  /**
   * @inheritDoc.
   */
  public function getSubmitHandler() {
    return FALSE;
  }

}