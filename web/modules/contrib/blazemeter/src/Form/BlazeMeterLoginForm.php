<?php


namespace Drupal\blazemeter\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class BlazeMeterLoginForm extends FormBase {

  public function getFormId() {
    return 'blazemeter_login_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#action'] = 'blazemeter-login';
    $form['error_message'] = array(
      '#type' => 'textearea',
      '#default_value' => '',
    );
    $form['email'] = array(
      '#type' => 'textfield',
      '#required' => TRUE,
      '#attributes' => array(
        'class' => array('login-email'),
        'placeholder' => t('E-mail')),
      '#prefix' => '<div style="display: none;" id="blazemeter-login-modal"><strong class="title">Log in</strong><div style="display:none;" class="glyphicon-log-in"><p></p></div><div style="display:none;" class="reg-error-message"></div>',
    );

    $form['password'] = array(
      '#type' => 'password',
      '#required' => TRUE,
      '#attributes' => array(
        'class' => array('login-password'),
        'placeholder' => t('Password')),
    );

    $form['submit-login'] = array(
      '#value' => 'Login',
      '#name' => 'Login',
      '#type' => 'submit',
      '#attributes' => array(
        'class' => array('use-ajax-submit'),
        'disabled' => true,
      ),
    );
    return $form;
  }

  public function getEditableConfigNames() {
    return ['blazemeter.settings'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    parent::submitForm($form, $form_state);

  }

}

?>