<?php

/**
 * @file
 * Contains \Drupal\blazemeter\Form\BlazemeterUserForm.
 */

namespace Drupal\blazemeter\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BlazemeterUserForm.
 *
 * @package Drupal\blazemeter\Form
 */
class BlazemeterUserForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $blazemeter_user = $this->entity;
    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => 255,
      '#default_value' => $blazemeter_user->username(),
      '#description' => $this->t("Username for the Blazemeter user."),
      '#required' => TRUE,
    );
    $form['password'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#maxlength' => 255,
      '#default_value' => $blazemeter_user->password(),
      '#description' => $this->t("Password for the Blazemeter user."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $blazemeter_user->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\blazemeter\Entity\BlazemeterUser::load',
      ),
      '#disabled' => !$blazemeter_user->isNew(),
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $blazemeter_user = $this->entity;
    $status = $blazemeter_user->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %username Blazemeter user.', [
          '%username' => $blazemeter_user->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %username Blazemeter user.', [
          '%username' => $blazemeter_user->label(),
        ]));
    }
    $form_state->setRedirectUrl($blazemeter_user->urlInfo('collection'));
  }

}
