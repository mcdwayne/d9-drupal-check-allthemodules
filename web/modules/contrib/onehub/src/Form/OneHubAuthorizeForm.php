<?php

namespace Drupal\onehub\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\onehub\OneHubOauth;
use Drupal\onehub\OneHubApi;

/**
 * Authorize Form Page for OneHub.
 */
class OneHubAuthorizeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'onehub_authorize_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Check both methods to make sure we have auth.
    $token = \Drupal::config('onehub.settings')->get('onehub_access_token');

    // If we have a token.
    if (!empty($token)) {
      $oh = new OneHubApi();
      $api = $oh->checkToken();
    }

    // Show the submit or not.
    if (empty($token) || !$api) {
      // We are not Authorized, instruct the people.
      $form['title'] = [
        '#type' => 'item',
        '#markup' => '<h3>OneHub is not authorized on this site.  Click Authorize below and follow the prompts.<h3>
                      <h4>Also, make sure you are logged out of your OneHub developer account as well.',
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => t('Authorize'),
        '#prefix' => '<center>',
        '#suffix' => '</center>',
      ];
    }
    else {
      // We are good!
      $form['title'] = [
        '#type' => 'item',
        '#markup' => '<center><h3>OneHub is authorized, you may carry on.<h3></center>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Instantiate the OneHub request and grab the code.
    $oh = new OneHubOauth();
    $oh->getAuthCode();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
