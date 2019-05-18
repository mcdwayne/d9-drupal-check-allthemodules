<?php

/**
 * @file
 * Contains \Drupal\miniorange_saml\Form\MiniorangeOAuthClientCustomerSetup.
 */

namespace Drupal\miniorange_oauth_client\Form;

use Drupal\miniorange_oauth_client\MiniorangeOAuthClientCustomer;
//use Drupal\miniorange_oauth_client\MiniorangeOAuthClientCustomerSetup;
use Drupal\Core\Form\FormBase;
use Drupal\miniorange_oauth_client\MiniorangeOAuthClientSupport;

class MiniorangeOAuthClientCustomerSetup extends FormBase {

  public function getFormId() {
    return 'miniorange_oauth_client_customer_setup';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state)
  {
    global $base_url;

    $current_status = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_status');
    $form['markup_library'] = array(
      '#attached' => array(
          'library' => array(
              "miniorange_oauth_client/miniorange_oauth_client.admin",
          )
      ),
    );
    if ($current_status == 'VALIDATE_OTP') {
        $form['miniorange_oauth_client_customer_otp_token'] = array(
          '#type' => 'textfield',
          '#title' => t('OTP'),
        );

        $form['miniorange_oauth_client_customer_validate_otp_button'] = array(
          '#type' => 'submit',
          '#value' => t('Validate OTP'),
          '#submit' => array('::miniorange_oauth_client_validate_otp_submit'),
        );

        $form['miniorange_oauth_client_customer_setup_resendotp'] = array(
          '#type' => 'submit',
          '#value' => t('Resend OTP'),
          '#submit' => array('::miniorange_oauth_client_resend_otp'),
        );

        $form['miniorange_oauth_client_customer_setup_back'] = array(
          '#type' => 'submit',
          '#value' => t('Back'),
          '#submit' => array('::miniorange_oauth_client_back'),
        );

        return $form;
      }
      elseif ($current_status == 'PLUGIN_CONFIGURATION')
      {
          //$form['header_top_style_1'] = array('#markup' => '<div class="mo_oauth_client_table_layout_1">',
          //);

          
        $form['markup_top_message'] = array(
          '#markup' => '<div class="mo_oauth_client_table_layout">'
          );
          $form['markupboit_message'] = array(
            '#markup' => '<div class="mo_oauth_client_table_layout container">Thank you for registering with miniOrange<h4>Your Profile: </h4>'
            );
        $header = array(
          'email' => array(
              'data' => t('Customer Email')
          ),
          'customerid' => array(
               'data' => t('Customer ID')
          ),
          'token' => array(
               'data' => t('Token Key')
          ),
          'apikey' => array(
               'data' => t('API Key')
          ),
        );

        $options = [];

        $options[0] = array(
          'email' => \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_email'),
          'customerid' => \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_id'),
          'token' => \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_token'),
          'apikey' => \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_api_key'),
        );

        $form['fieldset']['customerinfo'] = array(
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $options,
        );

        $form['main_layout_div_end'] = array(
            '#markup' => '<br><br><br><br><br><br><br><br><br></div></div></div>',
        );

          $form['markup_idp_attr_header_top_support'] = array('#markup' => '<div class="mo_oauth_client_table_layout_support_1">',
          );

          $form['markup_support_1'] = array(
              '#markup' => '<h3>Support</h3></h3><div>Need any help? Just send us a query so we can help you.<br /><br /></div>',
          );

          $form['miniorange_oauth_client_email_address_support'] = array(
              '#type' => 'textfield',
              '#attributes' => array('placeholder' => 'Enter your Email'),
              '#required' => TRUE,
          );

          $form['miniorange_oauth_client_phone_number_support'] = array(
              '#type' => 'textfield',
              '#attributes' => array('placeholder' => 'Enter your Phone Number'),
          );

          $form['miniorange_oauth_client_support_query_support'] = array(
              '#type' => 'textarea',
              '#clos' => '10',
              '#rows' => '5',
              '#attributes' => array('placeholder' => 'Write your query here'),
              '#required' => TRUE,
          );

          $form['miniorange_oauth_client_support_submit_click'] = array(
              '#type' => 'submit',
              '#value' => t('Submit Query'),
              '#submit' => array('::saved_support'),
          );

          $form['miniorange_oauth_client_support_note'] = array(
              '#markup' => '<div><br/>If you want custom features in the plugin, just drop an email to <a href="mailto:info@miniorange.com">info@miniorange.com</a></div>'
          );

          $form['miniorange_oauth_client_support_div_cust'] = array(

              '#markup' => '</div></div>'
          );

        return $form;
      }

      $form['header_top_style_1'] = array('#markup' => '<div class="mo_oauth_client_table_layout_1">',
      );

      $form['markup_top'] = array(
          '#markup' => '<div class="mo_oauth_client_table_layout container">'
      );

    $form['markup_14'] = array('#markup' => '<h3>Register with miniOrange</h3>');

    $form['markup_15'] = array(
      '#markup' => 'Just complete the short registration below to configure' . ' the OAuth Client Plugin. Please enter a valid email id <br>that you have' . ' access to. You will be able to move forward after verifying an OTP' . ' that we will send to this email.'
      );

    $form['miniorange_oauth_client_customer_setup_username'] = array(
      '#type' => 'textfield',
      '#title' => t('Email'),
    );

    $form['miniorange_oauth_client_customer_setup_phone'] = array(
      '#type' => 'textfield',
      '#title' => t('Phone'),
    );

    $form['markup_16'] = array(
      '#markup' => '<b>NOTE:</b> We will only call if you need support.'
      );

    $form['miniorange_oauth_client_customer_setup_password'] = array(
      '#type' => 'password_confirm',
    );

    $form['miniorange_oauth_client_customer_setup_button'] = array(
      '#type' => 'submit',
      '#value' => t('Register'),
    );

    $form['markup_divEnd'] = array(
      '#markup' => '</div></div></div>'
  );

  $form['markup_idp_attr_header_top_support'] = array('#markup' => '<div class="mo_oauth_client_table_layout_support_1">',
  );

  $form['markup_support_1'] = array(
      '#markup' => '<h3>Support</h3></h3><div>Need any help? Just send us a query so we can help you.<br /><br /></div>',
  );

  $form['miniorange_oauth_client_email_address_support'] = array(
      '#type' => 'textfield',
      '#attributes' => array('placeholder' => 'Enter your Email'),
  );

  $form['miniorange_oauth_client_phone_number_support'] = array(
      '#type' => 'textfield',
      '#attributes' => array('placeholder' => 'Enter your Phone Number'),
  );

  $form['miniorange_oauth_client_support_query_support'] = array(
      '#type' => 'textarea',
      '#clos' => '10',
      '#rows' => '5',
      '#attributes' => array('placeholder' => 'Write your query here'),
  );

  $form['miniorange_oauth_client_support_submit_click'] = array(
      '#type' => 'submit',
      '#value' => t('Submit Query'),
      '#submit' => array('::saved_support'),
  );

  $form['miniorange_oauth_client_support_note'] = array(
      '#markup' => '<div><br/>If you want custom features in the plugin, just drop an email to <a href="mailto:info@miniorange.com">info@miniorange.com</a></div>'
  );

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $username = $form['miniorange_oauth_client_customer_setup_username']['#value'];
    $phone = $form['miniorange_oauth_client_customer_setup_phone']['#value'];
    $password = $form['miniorange_oauth_client_customer_setup_password']['#value']['pass1'];
    if(empty($username)||empty($password)){
      drupal_set_message(t('The <b><u>Email </u></b> and <b><u>Password</u></b> fields are mandatory.'), 'error');
      return;
  }
if (!valid_email_address($username)) {
        drupal_set_message(t('The email address <i>' . $username . '</i> is not valid.'), 'error');
        return;
}
    $customer_config = new MiniorangeOAuthClientCustomer($username, $phone, $password, NULL);
    $check_customer_response = json_decode($customer_config->checkCustomer());
    if ($check_customer_response->status == 'CUSTOMER_NOT_FOUND') {

      \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_admin_email', $username)->save();
      \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_admin_phone', $phone)->save();
      \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_admin_password', $password)->save();
      $send_otp_response = json_decode($customer_config->sendOtp());

      if ($send_otp_response->status == 'SUCCESS') {
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_tx_id', $send_otp_response->txId)->save();
        $current_status = 'VALIDATE_OTP';
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_status', $current_status)->save();
        drupal_set_message(t('Verify email address by entering the passcode sent to @username', [
          '@username' => $username
          ]));
      }
    }
    elseif ($check_customer_response->status == 'CURL_ERROR') {
      drupal_set_message(t('cURL is not enabled. Please enable cURL'), 'error');
    }
    else {
      $customer_keys_response = json_decode($customer_config->getCustomerKeys());

      if (json_last_error() == JSON_ERROR_NONE) {
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_id', $customer_keys_response->id)->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_admin_token', $customer_keys_response->token)->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_admin_email', $username)->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_admin_phone', $phone)->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_api_key', $customer_keys_response->apiKey)->save();
        $current_status = 'PLUGIN_CONFIGURATION';
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_status', $current_status)->save();
        drupal_set_message(t('Successfully retrieved your account.'));
      }
      else {
        drupal_set_message(t('Invalid credentials'), 'error');
      }
    }
  }

  public function miniorange_oauth_client_back(&$form, $form_state) {
    $current_status = 'CUSTOMER_SETUP';
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_status', $current_status)->save();
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_miniorange_oauth_client_customer_admin_email')->save();
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_oauth_client_customer_admin_phone')->save();
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_oauth_client_tx_id')->save();
    drupal_set_message(t('Register/Login with your miniOrange Account'),'status');
  }

  public function miniorange_oauth_client_resend_otp(&$form, $form_state) {
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_oauth_client_tx_id')->save();
    $username = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_email');
    $phone = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_phone');
    $customer_config = new MiniorangeOAuthClientCustomer($username, $phone, NULL, NULL);
    $send_otp_response = json_decode($customer_config->sendOtp());
    if ($send_otp_response->status == 'SUCCESS') {
      // Store txID.
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_tx_id', $send_otp_response->txId)->save();
        $current_status = 'VALIDATE_OTP';
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_status', $current_status)->save();
        drupal_set_message(t('Verify email address by entering the passcode sent to @username', array('@username' => $username)));
    }
  }

  public function miniorange_oauth_client_validate_otp_submit(&$form, $form_state) {
    $otp_token = $form['miniorange_oauth_client_customer_otp_token']['#value'];
    $username = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_email');
    $phone = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_phone');
    $tx_id = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_tx_id');
    $customer_config = new MiniorangeOAuthClientCustomer($username, $phone, NULL, $otp_token);
    $validate_otp_response = json_decode($customer_config->validateOtp($tx_id));

    if ($validate_otp_response->status == 'SUCCESS')
    {
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_oauth_client_tx_id')->save();
        $password = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_password');
        $customer_config = new MiniorangeOAuthClientCustomer($username, $phone, $password, NULL);
        $create_customer_response = json_decode($customer_config->createCustomer());
        if ($create_customer_response->status == 'SUCCESS') {
            $current_status = 'PLUGIN_CONFIGURATION';
            \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_status', $current_status)->save();
            \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_admin_email', $username)->save();
            \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_admin_phone', $phone)->save();
            \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_admin_token', $create_customer_response->token)->save();
            \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_id', $create_customer_response->id)->save();
            \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_customer_api_key', $create_customer_response->apiKey)->save();
            drupal_set_message(t('Customer account created.'));
        }
        else {
            drupal_set_message(t('Error creating customer'), 'error');
        }
    }
    else {
        drupal_set_message(t('Error validating OTP'), 'error');
    }
  }

  function saved_support($form, &$form_state)
  {
      $email = $form['miniorange_oauth_client_email_address_support']['#value'];
      $phone = $form['miniorange_oauth_client_phone_number_support']['#value'];
      $query = $form['miniorange_oauth_client_support_query_support']['#value'];
      if(empty($email)||empty($query)){
        drupal_set_message(t('The <b><u>Email Address</u></b> and <b><u>Query</u></b> fields are mandatory.'), 'error');
        return;
    }
  if (!valid_email_address($email)) {
          drupal_set_message(t('The email address <i>' . $email . '</i> is not valid.'), 'error');
          return;
  }
      $support = new MiniorangeOAuthClientSupport($email, $phone, $query);
      $support_response = $support->sendSupportQuery();
      if ($support_response) {
          drupal_set_message(t('Support query successfully sent'));
      } else {
          drupal_set_message(t('Error sending support query'), 'error');
      }
  }

}