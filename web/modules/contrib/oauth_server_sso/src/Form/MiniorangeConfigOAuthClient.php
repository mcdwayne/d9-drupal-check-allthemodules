<?php

/**
 * @file
 * Contains \Drupal\oauth_server_sso\Form\MiniorangeConfigOAuthClient.
 */

namespace Drupal\oauth_server_sso\Form;
//use Drupal\oauth_server_sso\MiniorangeConfigOAuthClient;
use Drupal\Core\Form\FormBase;
use Drupal\oauth_server_sso\handler;

class MiniorangeConfigOAuthClient extends FormBase {

  public function getFormId() {
    return 'oauth_server_sso_config_client';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state)
  {
    if (\Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_customer_admin_email') == NULL || \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_customer_id') == NULL
    || \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_customer_admin_token') == NULL || \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_customer_api_key') == NULL) {
        \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->set('oauth_server_sso_disabled', TRUE)->save();
        $form['header'] = array(
            '#markup' => '<center><h3>You need to register with miniOrange before using this module.</h3></center>',
          );
  }
  else{
    \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->set('oauth_server_sso_disabled', FALSE)->save();
  }

      global $base_url;
      $stat = '';
      $module_path = drupal_get_path('module', 'oauth_server_sso');
      $finalpath = $base_url;
      $stat = \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_add_client_status');
      $form['#prefix'] = '<div style="background-color: rgb(241,241,241) ; padding: 20px">';
      $form['#suffix'] = '</div>';

      if($stat == '')
      {
          $form['oauth_server_sso_msg_1'] = array(
              '#markup' => "
                  <div style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%' id='enable_ldap'>
                      <h1><b>Add OAuth Client</b></h1><br>
                  </div>",
          );
          $form['oauth_server_sso_client_name'] = array(
            '#type' => 'textfield',
              '#prefix' => "<div  style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%'><h4>Client Name: </h4>",
              '#suffix' => "</div>",
              '#disabled' => \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_disabled'),
              '#id' => 'firstname',
              '#required' => 'true',
            '#default_value' => \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_client_name'),
          );
          $form['oauth_server_sso_redirect_url'] = array(
            '#type' => 'textfield',
              '#prefix' => "<div  style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%'><h4>Authorized Redirect URL: </h4>",
              '#suffix' => "</div>",
              '#disabled' => \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_disabled'),
              '#id' => 'firstname',
              '#required' => 'true',
            '#default_value' => \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_redirect_url'),
          );
          $form['next_step_1'] = array(
              '#type' => 'submit',
              '#prefix' => "<div  style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%'>",
              '#suffix' => "</div>",
              '#id' => 'button_config',
              '#value' => t('NEXT'),
              '#disabled' => \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_disabled'),
              '#submit' => array('::oauth_server_sso_next_1'),
              '#attributes' => array('class'=>array('my-form-class')),
          );
      }
      else
      {
          $form['oauth_server_sso_msg_1'] = array(
              '#markup' => "
                  <div style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%' id='enable_ldap'>
                      <h1><b>Add OAuth Client</b></h1><br>
                  </div>",
          );
          $form['oauth_server_sso_client_name'] = array(
            '#type' => 'textfield',
              '#prefix' => "<div  style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%'><h4>Client Name: </h4>",
              '#suffix' => "</div>",
              '#id' => 'firstname',
              '#disabled' => true,
              '#required' => 'true',
            '#default_value' => \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_client_name'),
          );
          $form['oauth_server_sso_redirect_url'] = array(
            '#type' => 'textfield',
              '#prefix' => "<div  style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%'><h4>Authorized Redirect URL: </h4>",
              '#suffix' => "</div>",
              '#id' => 'firstname',
              '#required' => 'true',
            '#default_value' => \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_redirect_url'),
          );
          $form['oauth_server_sso_client_id'] = array(
            '#type' => 'textfield',
              '#prefix' => "<div  style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%'><h4>Client ID: </h4>",
              '#suffix' => "</div>",
              '#id' => 'firstname',
              '#disabled' => true,
            '#default_value' => \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_client_id'),
          );
          $form['oauth_server_sso_client_secret'] = array(
            '#type' => 'textfield',
              '#prefix' => "<div  style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%'><h4>Client Secret: </h4>",
              '#suffix' => "</div>",
              '#id' => 'firstname',
              '#disabled' => 'true',
            '#default_value' => \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_client_secret'),
          );
          $form['oauth_server_sso_delete_client'] = array(
              '#type' => 'submit',
              '#prefix' => "<div  style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%'>",
              '#suffix' => "</div>",
              '#id' => 'button_config',
              '#value' => t('Delete Client'),
              '#submit' => array('::oauth_server_sso_delete_client'),
              '#attributes' => array('class'=>array('my-form-class')),
          );
          $form['next_step_1'] = array(
              '#type' => 'submit',
              '#prefix' => "<div  style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%'>",
              '#suffix' => "</div><br><br>",
              '#id' => 'button_config',
              '#value' => t('Update'),
              '#submit' => array('::oauth_server_sso_next_2'),
              '#attributes' => array('class'=>array('my-form-class')),
          );

          $form['oauth_server_sso_markup1']= array(
              '#prefix' => "<div  style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%'>",
              '#suffix' => "</div>",
              '#markup' => "<h2>Endpoint Urls</h2>
                            <p>You can configure below endpoints in your OAuth client.<p>",
          );
          $form['oauth_server_sso_endpoints'] = array(
              '#prefix' => "<div  style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%'>",
              '#suffix' => "</div><br><br>",
              '#markup' =>
              "<table>
                <tr><td><b>Authorize Endpoint </b> : </td><td>$finalpath/authorize</td></tr>
                <tr><td><b>Access Token Endpoint </b> : </td><td>$finalpath/access_token</td></tr>
                <tr><td><b>Get User Info Endpoint </b> : </td><td>$finalpath/user_info</td></tr>
                <tr><td><b>Scope </b> : </td><td>profile</td></tr>
            </table>",
          );
      }
      
      return $form;

 }

 function oauth_server_sso_next_1(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)
 {
     if($form['oauth_server_sso_client_name']['#value'] != '')
     {
         $client_name = $form['oauth_server_sso_client_name']['#value'];
         \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->set('oauth_server_sso_client_name',$client_name)->save();
     }
     if($form['oauth_server_sso_redirect_url']['#value'] != '')
     {
         $redirect_url = $form['oauth_server_sso_redirect_url']['#value'];
         \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->set('oauth_server_sso_redirect_url',$redirect_url)->save();
     }
     $client_id = handler::generateRandom(30);
     $client_secret = handler::generateRandom(30);
     \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->set('oauth_server_sso_client_id',$client_id)->save();
     \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->set('oauth_server_sso_client_secret',$client_secret)->save();
     \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->set('oauth_server_sso_add_client_status','review')->save();

     drupal_set_message(t('Configurations saved successfully.'));
 }
 function oauth_server_sso_next_2(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)
 {
     if($form['oauth_server_sso_client_name']['#value'] != '')
     {
         $client_name = $form['oauth_server_sso_client_name']['#value'];
       //  variable_set('oauth_server_sso_client_name',$client_name);
         \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->set('oauth_server_sso_client_name',$client_name)->save();
     }
     if($form['oauth_server_sso_redirect_url']['#value'] != '')
     {
         $redirect_url = $form['oauth_server_sso_redirect_url']['#value'];
  //       variable_set('oauth_server_sso_redirect_url',$redirect_url);
         \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->set('oauth_server_sso_redirect_url',$redirect_url)->save();
     }
     if($form['oauth_server_sso_client_id']['#value'] != '')
     {
         $client_id = $form['oauth_server_sso_client_id']['#value'];
         //variable_set('oauth_server_sso_client_id',$client_id);
         \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->set('oauth_server_sso_client_id',$client_id)->save();
     }
     if($form['oauth_server_sso_client_secret']['#value'] != '')
     {
         $client_secret = $form['oauth_server_sso_client_secret']['#value'];
         //variable_set('oauth_server_sso_client_secret',$client_secret);
         \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->set('oauth_server_sso_client_secret',$client_secret)->save();
     }
     //variable_set('oauth_server_sso_status','review');
     \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->set('oauth_server_sso_add_client_status','review')->save();
     drupal_set_message(t('Configurations saved successfully.'));
 }
 function oauth_server_sso_delete_client(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)
 {
    \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->clear('oauth_server_sso_client_name')->save();
    \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->clear('oauth_server_sso_client_id')->save();
    \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->clear('oauth_server_sso_client_secret')->save();
    \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->clear('oauth_server_sso_redirect_url')->save();
    \Drupal::configFactory()->getEditable('oauth_server_sso.settings')->clear('oauth_server_sso_add_client_status')->save();
 }

 function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state){
 }

   function saved_support(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)
   {
        $email = $form['oauth_server_sso_email_address_support']['#value'];
        $phone = $form['oauth_server_sso_phone_number_support']['#value'];
        $query = $form['oauth_server_sso_support_query_support']['#value'];
        if($email != null)
        {
            $support = new MiniorangeOAuthServerSupport($email, $phone, $query);
            $support_response = $support->sendSupportQuery();
            if ($support_response) {
                drupal_set_message(t('Support query successfully sent'));
            } else {
                drupal_set_message(t('Error sending support query'), 'error');
            }
        }
        else{
            print_r('Email can not be empty for sending a support quesry. Please fill the email and try again.');exit;
        }
   }

}