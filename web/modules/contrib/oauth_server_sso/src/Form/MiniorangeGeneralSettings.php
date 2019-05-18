<?php

/**
 * @file
 * Contains \Drupal\oauth_server_sso\Form\MiniorangeGeneralSettings.
 */

namespace Drupal\oauth_server_sso\Form;

use Drupal\Core\Form\FormBase;
use Drupal\oauth_server_sso\MiniorangeOAuthServerSupport;

class MiniorangeGeneralSettings extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_general_settings';
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
        $form['#prefix'] = '<div style="background-color: rgb(241,241,241) ; padding: 20px">';
        $form['#suffix'] = '</div>';
        $form['oauth_server_sso_msg_1'] = array(
                    '#markup' => "
                        <div style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%' id='enable_ldap'>
                            <h1><b>General Settings</b></h1><br>
                        </div>",
        );
        $form['oauth_server_sso_accesstoken_expiry'] = array(
                    '#type' => 'textfield',
                    '#prefix' => "<div  style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%'><h4>Access Token Expiry Time: </h4>",
                    '#placeholder' => 'in seconds',
                    '#description' =>'<b>[premium feature]</b>',
                    '#suffix' => "</div>",
                    '#id' => 'firstname',
                    '#disabled' =>'true',
                    '#required' => 'true',
                    '#default_value' =>\Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_accesstoken_expiry'),
                    '#attributes' => array('placeholder' => 'in seconds'),
        );
        $form['oauth_server_sso_refreshtoken_expiry'] = array(
                    '#type' => 'textfield',
                    '#prefix' => "<div  style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%'><h4>Refresh Token Expiry Time: </h4>",
                    '#suffix' => "</div>",
                    '#description' => '<b>[premium feature]</b>',
                    '#id' => 'firstname',
                    '#disabled' =>'true',
                    '#required' => 'true',
                    '#default_value' => \Drupal::config('oauth_server_sso.settings')->get('oauth_server_sso_refreshtoken_expiry'),
                    '#attributes' => array('placeholder' => 'in seconds'),
        );
        $form['next_step_1'] = array(
                '#type' => 'submit',
                '#prefix' => "<div  style='background-color: white; padding: 10px ;margin-left: 20px; width: 70%'>",
                '#suffix' => "</div>",
                '#id' => 'button_config',
                '#value' => t('Save Settings'),
                '#submit' => array('::oauth_server_sso_save_general_settings'),
                '#attributes' => array('class'=>array('my-form-class')),
        );

        return $form;
}
public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
}
function oauth_server_sso_save_general_settings($form, $form_state)
{
    if($form['oauth_server_sso_refreshtoken_expiry']['#value'] != '')
    {
            $refresh__token_expiry = $form['oauth_server_sso_refreshtoken_expiry']['#value'];
            variable_set('oauth_server_sso_refresh_token_expiry',$refresh__token_expiry);
    }
    if($form['oauth_server_sso_accesstoken_expiry']['#value'] != '')
    {
            $access__token_expiry = $form['oauth_server_sso_accesstoken_expiry']['#value'];
            variable_set('oauth_server_sso_access_token_expiry',$access__token_expiry);
    }
    //drupal_set_message(t('Configurations saved successfully.'));
}
}