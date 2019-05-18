<?php

namespace Drupal\fancy_login\Form;

use Drupal\user\Form\UserLoginForm;

/**
 * Override of the Login Form, used by this module.
 */
class FancyLoginLoginForm extends UserLoginForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fancy_login_user_login_form';
  }

}
