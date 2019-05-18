<?php

namespace Drupal\druhels;


class FormHelper {

  /**
   * Remove system elements from GET form.
   */
  public static function cleanGetForm($form) {
    unset($form['form_id']);
    unset($form['form_build_id']);
    unset($form['form_token']);

    return $form;
  }

}
