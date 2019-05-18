<?php

namespace Drupal\bootstrap_login_authenticate;

/**
 * A trait containing helper methods for block definitions.
 */
trait BootstrapLoginAuthenticateTrait {

  /**
   * An String of output result.
   *
   * @var string
   */
  protected $output = '';

  /**
   * Returns the login block.
   *
   * @return Drupal\bootstrap_login_authenticate\Plugin\Block
   *   The login block method.
   */
  protected function getLoginBlock() {
    // Getting userlogin form.
    $form = \Drupal::formBuilder()->getForm('Drupal\user\Form\UserLoginForm');

    // Getting setting of user.
    $userRegister = \Drupal::config('user.settings')->get('register');

    $useloginLink = ($userRegister != USER_REGISTER_ADMINISTRATORS_ONLY) ? 1 : 0;

    $output = [
      'form' => $form,
      'login_link_access' => $useloginLink,
    ];

    return $output;
  }

  /**
   * Returns the Register block.
   *
   * @return Drupal\bootstrap_login_authenticate\Plugin\Block
   *   The register method.
   */
  protected function getRegisterBlock() {
    // Getting User Register form.
    $entity = \Drupal::entityTypeManager()->getStorage('user')->create([]);

    $formObject = \Drupal::entityTypeManager()
      ->getFormObject('user', 'register')
      ->setEntity($entity);

    $form = \Drupal::formBuilder()->getForm($formObject);

    // Getting setting of user.
    $userRegister = \Drupal::config('user.settings')->get('register');

    $useRegister = ($userRegister == USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL) ? 1 : 0;

    $output = [
      'form' => $form,
      'register_access' => $useRegister,
    ];

    return $output;
  }

  /**
   * Returns the forgot password block.
   *
   * @return Drupal\bootstrap_login_authenticate\Plugin\Block
   *   The forgot password method.
   */
  protected function getPasswordResetBlock() {
    // Getting User Reset Form form.
    $form = \Drupal::formBuilder()->getForm('Drupal\user\Form\UserPasswordForm');

    $output = [
      'form' => $form,
    ];

    return $output;
  }

}
