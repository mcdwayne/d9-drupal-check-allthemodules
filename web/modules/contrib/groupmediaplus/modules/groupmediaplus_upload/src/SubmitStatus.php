<?php

namespace Drupal\groupmediaplus_upload;

use Drupal\Core\Form\FormStateInterface;

class SubmitStatus {

  private static $formState;

  public static function getFormState() {
    return self::$formState;
  }

  public static function on($form, FormStateInterface $formState) {
    self::$formState = $formState;
  }

  public static function off() {
    self::$formState = NULL;
  }

}
