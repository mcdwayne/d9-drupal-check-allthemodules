<?php
/**
 * @file
 * Contains Attribute for miniOrange Oauth Server Module.
 */

 /**
 * Showing Settings form.
 */
namespace Drupal\oauth_server_sso\Form;

 class mOlicensing extends FormBase {

  public function getFormId() {
    return 'oauth_server_sso_molicensing';
  }


 public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state)
 {

     /*if (\Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_admin_email') == NULL || \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_customer_id') == NULL
        || \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_customer_admin_token') == NULL || \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_customer_api_key') == NULL) {
        $form['header'] = array(
          '#markup' => '<center><h3>You need to register with miniOrange before using this module.</h3></center>',
        );

        return $form;
      }
      */

      global $base_url;
      echo "hey";exit;
      return $form;
    }
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)
  {

  }

}