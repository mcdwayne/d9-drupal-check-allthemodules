<?php

/**
 * @file
 * Contains \Drupal\securesite\Form\SecuresiteLoginForm.
 */

namespace Drupal\securesite\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a user login form for securesite.
 */
class SecuresiteLoginForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'securesite_login_form';
  }

  /**
   * {@inheritdoc}
   */

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#theme'] = 'securesite_login_form';
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('User name'),
      '#maxlength' => USERNAME_MAX_LENGTH,
      '#size' => 15,
    );
    $form['pass'] = array(
      '#type' => 'password',
      '#title' => t('Password'),
      '#maxlength' => 60,
      '#size' => 15,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Log in'),
      '#weight' => 2,
    );
    if (\Drupal::moduleHandler()->moduleExists('openid')) {
      global $base_path;
      $style = '<style type="text/css" media="all">' . "\n" .
        '#securesite-user-login li.openid-link {' . "\n" .
        '  background:transparent url(' . $base_path . drupal_get_path('module', 'openid') . '/login-bg.png) no-repeat scroll 1px 0.35em;' . "\n" .
        '}' . "\n" .
        '</style>';
      drupal_add_html_head($style);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {


  }

}