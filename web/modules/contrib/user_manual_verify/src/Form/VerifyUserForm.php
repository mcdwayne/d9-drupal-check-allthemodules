<?php

namespace Drupal\user_manual_verify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to verify an unverified user.
 */
class VerifyUserForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'verify_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $user = \Drupal::routeMatch()->getParameter('user');

    $form['intro'] = ['#markup' => "<p><strong>Verify " . $user->label() . "?</strong></p>"];

    $form['user_id'] = [
      '#type' => 'hidden',
      '#value' => $user->id(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => "Verify " . $user->label()];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $user = \Drupal::routeMatch()->getParameter('user');
    $user->setLastAccessTime(time());
    $user->setLastLoginTime(time());
    $user->activate();
    $user->save();

    drupal_set_message($user->label() . ' is now verified.');

    $form_state->setRedirect('entity.user.collection');
  }

}
