<?php

namespace Drupal\fancy_login\Form;

/**
 * Overrides the user password form, for use with the fancy login module.
 */
class FancyLoginPasswordForm extends UserPasswordForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fancy_login_user_pass';
  }

}
