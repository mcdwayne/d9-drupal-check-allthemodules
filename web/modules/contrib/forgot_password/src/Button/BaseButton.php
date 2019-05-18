<?php
namespace Drupal\forgot_password\Button;

/**
 * Class BaseButton.
 *
 * @package Drupal\forgot_password\Button
 */
abstract class BaseButton implements ButtonInterface {

  /**
   * {@inheritdoc}
   */
  public function ajaxify() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmitHandler() {
    return FALSE;
  }

}