<?php

namespace Drupal\itsyouonline\Form;

use Drupal\user\RegisterForm;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure itsyouonline account for this site.
 */
class UserRegistrationForm extends RegisterForm {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'itsyouonline_new_user_form';
  }

  public function form(array $form, FormStateInterface $form_state) {
    $form['#cache'] = array('max-age' => 0);

    $form['itsyouonline_info'] = array(
      '#type' => 'markup',
      '#markup' => t('Complete this form to create a new account on this website to link it to your itsyou.online account.'),
      '#weight' => -100
    );

    return parent::form($form, $form_state);
  }
}
