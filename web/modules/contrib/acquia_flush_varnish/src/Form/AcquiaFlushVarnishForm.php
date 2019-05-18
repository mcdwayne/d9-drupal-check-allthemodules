<?php

/**
 * @file
 * Contains \Drupal\acquia_flush_varnish\Form\AcquiaFlushVarnishForm.
 */

namespace Drupal\acquia_flush_varnish\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Controller\ControllerBase;

/**
 * Default FormBase for the acquia_flush_varnish module.
 */
class AcquiaFlushVarnishForm extends FormBase {
  /**
   * Default getform id function.
   */
  public function getFormId() {
    return 'acquia_flush_varnish_form';
  }

  /**
   * Acquia cloud API credentials form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $form['acquia_flush_varnish']['email'] = [
      '#type' => 'textfield',
      '#title' => t('E-mail'),
      '#size' => 60,
      '#required' => TRUE,
      '#default_value' => $this->config('acquia_flush_varnish.settings')->get('acquia_flush_varnish_email'),
      '#attributes' => [
        'placeholder' => [
          'Enter your acquia cloud API e-mail'
          ]
        ],
      '#description' => t('Please enter your email.'),
    ];
    $form['acquia_flush_varnish']['private_key'] = [
      '#type' => 'textfield',
      '#title' => t('Private key'),
      '#default_value' => $this->config('acquia_flush_varnish.settings')->get('acquia_flush_varnish_privatekey'),
      '#size' => 60,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => [
          'Enter your acquia cloud API private key'
          ]
        ],
      '#description' => t('Please enter your private key.'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save API values'),
    ];
    return $form;

  }

  /**
   * Acquia cloud API credentials form validate.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $uname = $form_state->getValue(['acquia_flush_varnish', 'email']);
    $pword = $form_state->getValue(['acquia_flush_varnish', 'private_key']);
    $credentials = array($uname, $pword);
    $output = \Drupal\acquia_flush_varnish\Controller\AcquiaFlushVarnishController::getResponse("https://cloudapi.acquia.com/v1/sites.json", 'GET', $credentials);
    if (isset($uname) && valid_email_address($uname) != 1) {
      $form_state->setErrorByName('acquia_flush_varnish][email', $this->t('The e-mail address is not valid.'));
    }
    elseif (isset($output->message) && $output->message == 'Not authorized') {
      $form_state->setErrorByName('acquia_flush_varnish', $this->t('Acquia cloud API credentials is not valid.'));
    }
    else {
      if (isset($uname) && isset($pword) && !isset($output->message)) {
        $this->configFactory()->getEditable('acquia_flush_varnish.settings')->set('acquia_flush_varnish_email', $uname)->save();
        $this->configFactory()->getEditable('acquia_flush_varnish.settings')->set('acquia_flush_varnish_privatekey', $pword)->save();
        drupal_set_message(t('Acquia cloud API credentials saved successfully'));
      }
      else {
        $form_state->setErrorByName('acquia_flush_varnish', $this->t('Acquia cloud API credentials is not valid.'));
      }
    }
  }

  /**
   * Acquia cloud API credentials form submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
