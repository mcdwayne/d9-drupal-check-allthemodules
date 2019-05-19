<?php

/**
 * @file
 * Contains \Drupal\example\Form\webtexttoolSettingsForm
 */
namespace Drupal\webtexttool\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class webtexttoolRegisterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webtexttool_admin_register';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['webtexttool_register'] = array(
      '#prefix' => '<div class="intro">' . $this->t('Not yet registered? Registration is quick and easy. Fill in the below form and you will get a webtexttool account.'),
      '#suffix' => '</div>',
    );

    $form['webtexttool_user'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#default_value' => '',
      '#description' => $this->t('The email that should be registered.'),
    );

    $form['webtexttool_pass'] = array(
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => '',
      '#description' => $this->t('The password to connect to webtexttool.'),
    );

    $form['webtexttool_language'] = array(
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => array('en' => $this->t('English'), 'nl' => $this->t('Dutch')),
      '#default_value' => '',
      '#description' => $this->t('The default language of the tool itself. At this moment the tool itself is only avaible in Dutch and English.'),
    );
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate email.
    if (!\Drupal::service('email.validator')->isValid($form_state->getValue('email')) == TRUE ) {
      $form_state->setErrorByName('email', $this->t("The email adress'%email' is invalid.", array('%email' => $form_state->getValue('email'))));
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $user = $values['webtexttool_user'];
    $error = FALSE;

    if ($user == '' || !valid_email_address($user)) {
      $error = TRUE;
      $form_state->setErrorByName('webtexttool_user', $this->t('The provided email address is not valid'));
    }

    $pass = $values['webtexttool_pass'];
    if ($pass == '') {
      $error = TRUE;
      $form_state->setErrorByName('webtexttool_pass', $this->t('Please fill in a password.'));
    }
    $language = $values['webtexttool_language'];

    if (!$error) {
      \Drupal::service('webtexttool.webtexttool_controller')->webtexttoolRegister($user, $pass, $language);
    }
  }
}