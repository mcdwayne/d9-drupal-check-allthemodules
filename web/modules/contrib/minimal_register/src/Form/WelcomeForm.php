<?php

namespace Drupal\minimal_register\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;


class WelcomeForm extends FormBase {


  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'welcome_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    //Get module settings
    $config = $this->config('minimal_register.settings');
    //Generate Info Message
    $welcome_markup = '<div class="welcome-panel-title">' . $this->t('Click on the link to confirm your email') . '</div>';
    $welcome_message_text = $this->t($config->get('welcome_message'));
    $welcome_message_text_email = str_replace("@usermail", '<b>' . \Drupal::currentUser()->getEmail() . '</b>', $welcome_message_text);
    $welcome_markup .= '<div class="welcome-panel-message"><p>' . $welcome_message_text_email . '</p></div>';
    $form['info'] = [
      '#markup' => $welcome_markup,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Re-send Email'),
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = User::load(\Drupal::currentUser()->id());
    _user_mail_notify('register_no_approval_required',$account);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

}
