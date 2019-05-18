<?php

/**
 * @file
 * Contains \Drupal\miniorange_oauth_client\Form\MiniorangeConfigOAuthClient.
 */

namespace Drupal\miniorange_oauth_client\Form;
//use Drupal\miniorange_oauth_client\MiniorangeConfigOAuthClient;
use Drupal\Core\Form\FormBase;
use Drupal\miniorange_oauth_client\handler;

class MiniorangeConfigOAuthClient extends FormBase {

  public function getFormId() {
    return 'miniorange_oauth_client_config_clc';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state)
  {
    global $base_url;
    if (\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_email') == NULL || \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_id') == NULL
        || \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_admin_token') == NULL || \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_customer_api_key') == NULL) {
            \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_disabled', TRUE)->save();
            $form['header'] = array(
                '#markup' => '<center><h3>You need to register with miniOrange before using this module.</h3></center>',
              );
        }
        else{
          \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_disabled', FALSE)->save();
        }
    if(!empty(\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url')))
        $baseUrlValue = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url');
    else
        $baseUrlValue = $base_url;
    $login_path = '<a href='.$baseUrlValue.'/moLogin>Enter what you want to display on the link</a>';
    $module_path = drupal_get_path('module', 'miniorange_oauth_client');
    $google_path = $baseUrlValue.DIRECTORY_SEPARATOR.$module_path.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'google_guide.pdf';
    $strava_path = $baseUrlValue. DIRECTORY_SEPARATOR.$module_path.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'strava_guide.pdf';
    $fitbit_path = $baseUrlValue.DIRECTORY_SEPARATOR.$module_path.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'fitbit_guide.pdf';
    $facebook_path = $baseUrlValue.DIRECTORY_SEPARATOR.$module_path.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'facebook_guide.pdf';

    $miniorange_auth_client_client_id= '';
    $miniorange_auth_client_client_secret='';
    $miniorange_oauth_client_app = '';
    $miniorange_oauth_client_app_name = '';
    $miniorange_auth_client_display_name = '';
    $miniorange_auth_client_scope='';
    $miniorange_auth_client_authorize_endpoint='';
    $miniorange_auth_client_access_token_ep='';
    $miniorange_auth_client_user_info_ep='';
    $miniorange_auth_client_callback_uri ='';

    $miniorange_auth_client_client_id = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_client_id');
    $miniorange_oauth_client_app = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_app');
    $miniorange_oauth_client_app_name = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_app_name');
    $miniorange_auth_client_display_name = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_display_name');
    $miniorange_auth_client_client_secret = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_client_secret');
    $miniorange_auth_client_scope = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_scope');
    $miniorange_auth_client_authorize_endpoint = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_authorize_endpoint');
    $miniorange_auth_client_access_token_ep = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_access_token_ep');
    $miniorange_auth_client_user_info_ep = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_user_info_ep');
    $miniorange_auth_client_callback_uri = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_callback_uri');
    if(!empty(\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url')))
        $baseUrlValue = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url');
     else
        $baseUrlValue = $base_url;
    $miniorange_auth_client_callback_uri = $baseUrlValue."/mo_login";
    \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_callback_uri',$miniorange_auth_client_callback_uri)->save();

    $attachments['#attached']['library'][] = 'miniorange_oauth_client/miniorange_oauth_client.admin';
    $form['markup_library'] = array(
        '#attached' => array(
            'library' => array(
                "miniorange_oauth_client/miniorange_oauth_client.oauth_config",
                "miniorange_oauth_client/miniorange_oauth_client.admin",
                "miniorange_oauth_client/miniorange_oauth_client.testconfig",
            )
        ),
    );

    if((isset($_GET['action'])) && ($_GET['action'] == "delete"))
    {
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_oauth_client_app')->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_oauth_client_appval')->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_auth_client_client_id')->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_auth_client_app_name')->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_auth_client_display_name')->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_auth_client_client_secret')->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_auth_client_scope')->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_auth_client_authorize_endpoint')->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_auth_client_access_token_ep')->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_oauth_client_email_attr_val')->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_oauth_client_name_attr_val')->save();
        \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->clear('miniorange_auth_client_user_info_ep')->save();
        drupal_set_message("");
        $form['mo_back'] = array(
        '#markup' => "
        <br><h2>Configurations deleted successfully</h2><br><a class='btn btn-primary btn-large' style='padding:10px 22px;' href='config_clc'>Back</a>",
);
 drupal_set_message(t('Application Removed Successfully.'));
return $form;
    }
    if((isset($_GET['action'])) && ($_GET['action'] == "instr"))
    {
        if($miniorange_oauth_client_app=="Google")
        {
            $form['mo_google_instr'] = array(

                '#markup' => "<div class='form-item form-item-miniorange-oauth-client-google-instr'><strong>How to configure: <br></strong>
                <a href= 'https://plugins.miniorange.com/guide-to-enable-miniorange-oauth-client-for-drupal' target='_blank'>Click here to see the Guide for Configuring Google Apps as an OAuth Server. </a><br></div>",
            );

        }
        else if($miniorange_oauth_client_app=="Facebook")
        {
            $form['mo_facebook_instr'] = array(
                '#markup' => "<div class='form-item form-item-miniorange-oauth-client-google-instr'><strong>How to configure: <br></strong>
                <a href= 'https://plugins.miniorange.com/guide-to-enable-miniorange-oauth-client-for-drupal' target='_blank'>Click here to see the Guide for Configuring Facebook as an OAuth Server. </a><br></div>",
            );

        }
        else if($miniorange_oauth_client_app=="Eve Online")
        {
            $form['mo_eve_instr'] = array(
                '#markup' => "<div class='form-item form-item-miniorange-oauth-client-eve-instr'><strong>Instructions:</strong><ol><li>Log in to your EVE Online account</li><li>At EVE Online, go to Support. Request for enabling OAuthfor a third-party application.</li><li>At EVE Online, add a new project/application. GenerateClient ID and Client Secret.</li><li>At EVE Online, set Redirect URL as <b>https://auth.miniorange.com/moas/oauth/client/callback</b></li><li>Enter your Client ID and Client Secret above.</li><li>Click on the Save settings button.</li><li>Now logout and go to your site. You will see the following login link: <b>Login using Eve Online</b>.</li></ol></div>",
            );
        }
        else if($miniorange_oauth_client_app=="Strava")
        {
            $form['mo_strava_instr'] = array(
            '#markup' => "<div class='form-item form-item-miniorange-oauth-client-google-instr'><strong>How to configure: <br></strong>
            <a href= 'https://plugins.miniorange.com/guide-to-enable-miniorange-oauth-client-for-drupal' target='_blank'>Click here to see the Guide for Configuring Strava as an OAuth Server. </a><br></div>",
            );
        }
        else if($miniorange_oauth_client_app=="FitBit")
        {
            $form['mo_fitbit_instr'] = array(
            '#markup' => "<div class='form-item form-item-miniorange-oauth-client-google-instr'><strong>How to configure: <br></strong>
            <a href= 'https://plugins.miniorange.com/guide-to-enable-miniorange-oauth-client-for-drupal' target='_blank'>Click here to see the Guide for Configuring FitBit as an OAuth Server. </a><br></div>",
            );
        }
        else{
            $form['mo_other_instr'] = array(
                '#markup' => "<div class='form-item form-item-miniorange-oauth-client-other-instr'><br><strong>Instructions to configure custom OAuth Server:</strong><ol><li>Enter your Client ID and Client Secret above.</li><li>Click on the Save settings button.</li><li>Provide <b>Configure OAuth->Redirect/Callback URI</b> for your OAuth server Redirect URI.</li><li>Now logout and go to your site. You will see a link to login using your OAuth Server.</li></ol></div>",
            );

        }
        $form['mo_back'] = array(
            '#markup' => "<a class='btn btn-primary btn-large' style='padding:6px 12px;' href='config_clc'>Back</a>",
        );
        return $form;
    }
    if((isset($_GET['action'])) && ($_GET['action'] == "update"))
    {

    $form['miniorange_oauth_client_msgs'] = array(
        '#markup' => "<div style='background-color: lightblue'><li style='color: black'><b style='color: crimson'>Please Note:</b> Attribute Mapping is mandatory for login. Copy the Attributes and Roles from using Test Configuration and save them under the Attributes & Role Mapping tab.</li></div>",
    );
    $form['miniorange_oauth_callback'] = array(
        '#type' => 'textfield',
        '#title' => t('Callback/Redirect URL: '),
        '#id'  => 'callbackurl',
        '#default_value' => $miniorange_auth_client_callback_uri,
        '#disabled' => true,
        '#attributes' => array(
		),
    );

    $form['miniorange_oauth_app_name'] = array(
        '#type' => 'textfield',
        '#prefix' =>  "<tr id='mo_oauth_app_name_div'>",
        '#suffix' =>  "</tr>",
        '#required' => true,
        '#disabled' => true,
        '#default_value' => $miniorange_oauth_client_app_name,
        '#id'  => 'miniorange_oauth_client_app_name',
        '#title' => t('Custom App Name: '),
        '#attributes' => array(
            ),
    );
    $form['miniorange_oauth_client_display_name'] = array(
        '#type' => 'textfield',
        '#prefix' =>  "<tr id='mo_oauth_display_name_div'>",
        '#suffix' =>  "</tr>",
        '#id'  => 'miniorange_oauth_client_display_name',
        '#default_value' => $miniorange_auth_client_display_name,
        '#title' => t('Display Name: '),
        '#attributes' => array(
            ),
    );
    $form['miniorange_oauth_client_id'] = array(
        '#type' => 'textfield',
        '#prefix' =>  "<tr id='mo_oauth_client_id_div'>",
        '#id'  => 'miniorange_oauth_client_client_id',
        '#default_value' => $miniorange_auth_client_client_id,
        '#suffix' =>  "</tr>",
        '#required' => true,
        '#title' => t('Client Id: '),
        '#attributes' => array(
            ),
    );
    $form['miniorange_oauth_client_secret'] = array(
        '#type' => 'textfield',
        '#prefix' =>  "<tr id='mo_oauth_client_secret_div'>",
        '#suffix' =>  "</tr>",
        '#default_value' => $miniorange_auth_client_client_secret,
        '#required' => true,
        '#id'  => 'miniorange_oauth_client_client_secret',
        '#title' => t('Client Secret: '),
        '#attributes' => array(
            ),
    );
    $form['miniorange_oauth_client_scope'] = array(
        '#type' => 'textfield',
        '#prefix' =>  "<tr id='mo_oauth_scope_div'>",
        '#suffix' =>  "</tr>",
        '#id'  => 'miniorange_oauth_client_scope',
        '#default_value' => $miniorange_auth_client_scope,
        '#title' => t('Scope: '),
        '#attributes' => array(
            ),
    );
    $form['miniorange_oauth_client_authorize_endpoint'] = array(
        '#type' => 'textfield',
        '#prefix' =>  "<tr id='mo_oauth_authorize_end_point_div'>",
        '#suffix' =>  "</tr>",
        '#required' => true,
        '#default_value' => $miniorange_auth_client_authorize_endpoint,
        '#id'  => 'miniorange_oauth_client_auth_ep',
        '#title' => t('Authorize Endpoint: '),
        '#attributes' => array(
		),
    );
    $form['miniorange_oauth_client_access_token_endpoint'] = array(
        '#type' => 'textfield',
        '#prefix' =>  "<tr id='mo_oauth_access_end_point_div'>",
        '#suffix' =>  "</tr>",
        '#default_value' => $miniorange_auth_client_access_token_ep,
        '#required' => true,
        '#id'  => 'miniorange_oauth_client_access_token_ep',
        '#title' => t('Access Token Endpoint: '),
        '#attributes' => array(
		),
    );
    $form['miniorange_oauth_client_userinfo_endpoint'] = array(
        '#type' => 'textfield',
        '#default_value' => $miniorange_auth_client_user_info_ep,
        '#prefix' =>  "<tr id='mo_oauth_user_info_div'>",
        '#suffix' =>  "</tr>",
        '#required' => true,
        '#id'  => 'miniorange_oauth_client_user_info_ep',
        '#title' => t('Get User Info Endpoint: '),
        '#attributes' => array(
		),
    );

    $form['miniorange_oauth_client_config_submit'] = array(
        '#type' => 'submit',
        '#name'=>'submit',
        '#id' => 'button_config',
        '#value' => t('Save'),

    );
global $base_url;
if(!empty(\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url')))
        $baseUrlValue = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url');
else
    $baseUrlValue = $base_url;

    $form['miniorange_oauth_client_test_config_button'] = array(
    '#value' => t('Test'),
    '#markup' => '<div id="base_Url" name="base_Url" data="'. $baseUrlValue.'"></div>
        <a id="testConfigButton" class="btn btn-primary btn-sm">Test Configuration</a>
    ',
    );
    $form['mo_back'] = array(
        '#markup' => "\t\t\t\t<a class='btn btn-primary btn-large' style='padding:6px 12px;' href='config_clc'>Back</a>",
    );

    $form['miniorange_oauth_login_link'] = array(

    '#prefix' =>  "<tr id='miniorange_oauth_login_link'>",
    '#suffix' =>  "</tr>",
    '#id'  => 'miniorange_oauth_login_link',
    '#markup' => "<br><br><div style='background-color: rgba(173,216,230,0.3); padding: 15px; '>
        <br><strong><font size='5'>Instructions to add login link to different pages in your Drupal site: <br><br></font></strong>
        <font size='3'>After completing your configurations, by default you will see a login link on your drupal site's login page. However, if you want to add login link somewhere else, please follow the below given steps:</font>
        <div style='padding-left: 15px;padding-top:5px;'>
        <font size='2'>
        <li style='padding: 3px'>Go to <b>Structure</b> -> <b>Blocks</b></li>
        <li style='padding: 3px'> Click on <b>Add block</b></li>
        <li style='padding: 3px'>Enter <b>Block Title</b> and the <b>Block description</b></li>
        <li style='padding: 3px'>Under the <b>Block body</b> enter the following URL:
            <ol><pre>&lt;a href=&lt;your domain&gt;/moLogin&lt;enter text you want to show on the link&lt;/a&gt;</pre></ol>
            <ol>For example: If your domain name is <b>https://www.miniorange.com</b> then, enter: <b>&lt;a href= 'https://www.miniorange.com/moLogin'> Click here to Login&lt;/a&gt;</b> in the <b>Block body</b> textfield </ol>
        </li>
        <li style='padding: 3px'>From the text filtered dropdown select either <b>Filtered HTML</b> or <b>Full HTML</b></li>
        <li style='padding: 3px'>From the division under <b>REGION SETTINGS</b> select where do you want to show the login link</li>
        <li style='padding: 3px'>Click on the <b>SAVE block</b> button to save your settings</li><br>
        </font>
        </div>
        </div>",

    '#attributes' => array(
		),
    );

        return $form;
    }
    if($miniorange_oauth_client_app != NULL)
    {

     $form['miniorange_oauth_client_msgs'] = array(
        '#markup' => "<div style='background-color: rgba(173,216,230,0.2)'><li style='color: black'><b style='color: crimson'>Please Note:</b> Attribute Mapping is mandatory for login. Copy the Attributes and Roles from Test Configuration and save them under the Attributes & Role Mapping tab.</li></div>",

        );

        $form['miniorange_oauth_client_add']= array(
            '#markup' => "<br><p style='color: black;'>You can add only 1 application in the free plugin. Upgrade to <a style='color: green;' href=$baseUrlValue/admin/config/people/miniorange_oauth_client/licensing>enterprise</a> version to add multiple applications.</p>",
        );

    $form['miniorange_oauth_client_app_table']= array(
        '#markup' => "<head>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>
        </head><table class='tableborder'><br>
        <tr><th><b>Name</b></th><th>Action</th></tr>
        <tr><td><b>$miniorange_oauth_client_app_name</b></td><td><a href='config_clc?&action=update&app=$miniorange_oauth_client_app_name'>Edit Application</a> | <a href='config_clc?&action=update&app=$miniorange_oauth_client_app_name'>
    Test Configuration</a> | <a href='mapping#attribute-mapping'>Attribute Mapping</a> | <a href='mapping#role-mapping'>Role Mapping</a> | <a href='config_clc?&action=delete&app=$miniorange_oauth_client_app_name'>Delete</a> | <a href='config_clc?&action=instr&app=$miniorange_oauth_client_app#howtoconfigure'>How to Configure?</a></td></tr>"
    );
    return $form;
    }

    $form['miniorange_oauth_client_app_options'] = array(
        '#type' => 'value',
        '#id' => 'miniorange_oauth_client_app_options',
        '#value' => array(
                          'Google' => t('Google'),
                          'Facebook' => t('Facebook'),
                          'Windows Account' => t('Windows Account'),
                          'Strava' => t('Strava'),
                          'FitBit' => t('FitBit'),
                          'Custom' => t('Custom OAuth 2.0 Provider')),
    );
    $form['miniorange_oauth_client_app'] = array(
        '#title' => t('Select Application: '),
        '#id' => 'miniorange_oauth_client_app',
        '#type' => 'select',
        '#disabled' => \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_disabled'),
        '#required' => true,
        '#description' => "Select an OAuth Server",
        '#options' => $form['miniorange_oauth_client_app_options']['#value'],
    );

    $form['#prefix'] =  "<table><tr>";
    $form['#suffix'] =  "</tr></table>";

    $form['miniorange_oauth_client_google_instr'] = array(
        '#prefix' =>  "<tr id='miniorange_oauth_client_google_instr'>",
        '#suffix' =>  "</tr>",
        '#id'  => 'miniorange_oauth_client_google_instr',
        '#markup' => "<div class='form-item form-item-miniorange-oauth-client-google-instr'><strong>How to configure: <br></strong>
        <a href= 'https://plugins.miniorange.com/guide-to-enable-miniorange-oauth-client-for-drupal' target='_blank'>Click here to see the Guide for Configuring Google Apps as an OAuth Server. </a><br></div>",
        '#attributes' => array(
		),
    );

    $form['miniorange_oauth_client_facebook_instr'] = array(
        '#prefix' =>  "<tr id='miniorange_oauth_client_facebook_instr'>",
        '#suffix' =>  "</tr>",
        '#id'  => 'miniorange_oauth_client_facebook_instr',
        '#markup' => "<div class='form-item form-item-miniorange-oauth-client-facebook-instr'><strong>How to configure: <br></strong>
        <a href= 'https://plugins.miniorange.com/guide-to-enable-miniorange-oauth-client-for-drupal' target='_blank'>Click here to see the Guide for Configuring Facebook as an OAuth Server. </a><br></div>",
        '#attributes' => array(
        ),
    );
    $form['miniorange_oauth_client_eve_instr'] = array(
        '#prefix' =>  "<tr id='miniorange_oauth_client_eve_instr'>",
        '#suffix' =>  "</tr>",
        '#id'  => 'miniorange_oauth_client_eve_instr',
        '#markup' => "<div class='form-item form-item-miniorange-oauth-client-eve-instr'><strong>Instructions:</strong><ol><li>Log in to your EVE Online account</li><li>At EVE Online, go to Support. Request for enabling OAuthfor a third-party application.</li><li>At EVE Online, add a new project/application. GenerateClient ID and Client Secret.</li><li>At EVE Online, set Redirect URL as <b>https://auth.miniorange.com/moas/oauth/client/callback</b></li><li>Enter your Client ID and Client Secret above.</li><li>Click on the Save settings button.</li><li>Now logout and go to your site. You will see the following login link: <b>Login using Eve Online</b>.</li></ol></div>",
        '#attributes' => array(
		),
    );
    $form['miniorange_oauth_client_strava_instr'] = array(
        '#prefix' =>  "<tr id='miniorange_oauth_client_strava_instr'>",
        '#suffix' =>  "</tr>",
        '#id'  => 'miniorange_oauth_client_strava_instr',
        '#markup' => "<div class='form-item form-item-miniorange-oauth-client-strava-instr'><strong>How to configure: <br></strong>
        <a href= 'https://plugins.miniorange.com/guide-to-enable-miniorange-oauth-client-for-drupal' target='_blank'>Click here to see the Guide for Configuring Strava as an OAuth Server. </a><br></div>",
        '#attributes' => array(
        ),
    );
    $form['miniorange_oauth_client_fitbit_instr'] = array(
        '#prefix' =>  "<tr id='miniorange_oauth_client_fitbit_instr'>",
        '#suffix' =>  "</tr>",
        '#id'  => 'miniorange_oauth_client_fitbit_instr',
        '#markup' => "<div class='form-item form-item-miniorange-oauth-client-fitbit-instr'><strong>How to configure: <br></strong>
        <a href= 'https://plugins.miniorange.com/guide-to-enable-miniorange-oauth-client-for-drupal' target='_blank'>Click here to see the Guide for Configuring FitBit as an OAuth Server. </a><br></div>",
        '#attributes' => array(
        ),
    );

    $form['miniorange_oauth_client_other_instr'] = array(
        '#prefix' =>  "<tr id='miniorange_oauth_client_other_instr'>",
        '#suffix' =>  "</tr>",
        '#id'  => 'miniorange_oauth_client_other_instr',
        '#markup' => "<div class='form-item form-item-miniorange-oauth-client-other-instr'><br><strong>Instructions to configure custom OAuth Server:</strong><ol><li>Enter your Client ID and Client Secret above.</li><li>Click on the Save settings button.</li><li>Provide <b>Configure OAuth->Redirect/Callback URI</b> for your OAuth server Redirect URI.</li><li>Now logout and go to your site. You will see a link to login using your OAuth Server.</li></ol></div>",
        '#attributes' => array(
		),
    );


    $form['miniorange_oauth_callback'] = array(
        '#type' => 'textfield',
        '#title' => t('Callback/Redirect URL: '),
        '#id'  => 'callbackurl',
        '#default_value' => $miniorange_auth_client_callback_uri,
        '#disabled' => true,
        '#attributes' => array(
		),
    );

    $form['miniorange_oauth_app_name'] = array(
        '#type' => 'textfield',
        '#prefix' =>  "<tr id='mo_oauth_app_name_div'>",
        '#suffix' =>  "</tr>",
        '#required' => true,
        '#default_value' => $miniorange_oauth_client_app_name,
        '#id'  => 'miniorange_oauth_client_app_name',
        '#title' => t('Custom App Name: '),
        '#attributes' => array(
		),
    );
    $form['miniorange_oauth_client_display_name'] = array(
        '#type' => 'textfield',
        '#prefix' =>  "<tr id='mo_oauth_display_name_div'>",
        '#suffix' =>  "</tr>",
        '#id'  => 'miniorange_oauth_client_display_name',
        '#default_value' => $miniorange_auth_client_display_name,
        '#title' => t('Display Name: '),
        '#attributes' => array(
		),
    );
    $form['miniorange_oauth_client_id'] = array(
        '#type' => 'textfield',
        '#prefix' =>  "<tr id='mo_oauth_client_id_div'>",
        '#id'  => 'miniorange_oauth_client_client_id',
        '#default_value' => $miniorange_auth_client_client_id,
        '#suffix' =>  "</tr>",
        '#required' => true,
        '#title' => t('Client Id: '),
        '#description' => "You will get this value from your OAuth Server",
        '#attributes' => array(
		),
    );
    $form['miniorange_oauth_client_secret'] = array(
        '#type' => 'textfield',
        '#prefix' =>  "<tr id='mo_oauth_client_secret_div'>",
        '#suffix' =>  "</tr>",
        '#default_value' => $miniorange_auth_client_client_secret,
        '#required' => true,
        '#description' => "You will get this value from your OAuth Server",
        '#id'  => 'miniorange_oauth_client_client_secret',
        '#title' => t('Client Secret: '),
        '#attributes' => array(
		),
    );
    $form['miniorange_oauth_client_scope'] = array(
        '#type' => 'textfield',
        '#prefix' =>  "<tr id='mo_oauth_scope_div'>",
        '#suffix' =>  "</tr>",
        '#id'  => 'miniorange_oauth_client_scope',
        '#default_value' => $miniorange_auth_client_scope,
        '#description' => "You can edit the value of this field but we highly recommend not change the default values of this field",
        '#title' => t('Scope: '),
        '#attributes' => array(
		),
    );
    $form['miniorange_oauth_client_authorize_endpoint'] = array(
        '#type' => 'textfield',
        '#prefix' =>  "<tr id='mo_oauth_authorize_end_point_div'>",
        '#suffix' =>  "</tr>",
        '#required' => true,
        '#default_value' => $miniorange_auth_client_authorize_endpoint,
        '#id'  => 'miniorange_oauth_client_auth_ep',
        '#title' => t('Authorize Endpoint: '),
        '#attributes' => array(
		),
    );
    $form['miniorange_oauth_client_access_token_endpoint'] = array(
        '#type' => 'textfield',
        '#prefix' =>  "<tr id='mo_oauth_access_end_point_div'>",
        '#suffix' =>  "</tr>",
        '#default_value' => $miniorange_auth_client_access_token_ep,
        '#required' => true,
        '#id'  => 'miniorange_oauth_client_access_token_ep',
        '#title' => t('Access Token Endpoint: '),
        '#attributes' => array(
		),
    );
    $form['miniorange_oauth_client_userinfo_endpoint'] = array(
        '#type' => 'textfield',
        '#default_value' => $miniorange_auth_client_user_info_ep,
        '#prefix' =>  "<tr id='mo_oauth_user_info_div'>",
        '#suffix' =>  "</tr>",
        '#required' => true,
        '#id'  => 'miniorange_oauth_client_user_info_ep',
        '#title' => t('Get User Info Endpoint: '),
        '#attributes' => array(
		),
    );
    $form['miniorange_oauth_login_link'] = array(

        '#prefix' =>  "<tr id='miniorange_oauth_login_link'>",
        '#suffix' =>  "</tr>",
        '#id'  => 'miniorange_oauth_login_link',
        '#markup' => "<br><br><div style='background-color: rgba(173,216,230,0.3); padding: 15px; '>
            <br><strong><font size='5'>Instructions to add login link to different pages in your Drupal site: <br><br></font></strong>
            <font size='3'>After completing your configurations, by default you will see a login link on your drupal site's login page. However, if you want to add login link somewhere else, please follow the below given steps:</font>
            <div style='padding-left: 15px;padding-top:5px;'>
            <font size='2'>
            <li style='padding: 3px'>Go to <b>Structure</b> -> <b>Blocks</b></li>
            <li style='padding: 3px'> Click on <b>Add block</b></li>
            <li style='padding: 3px'>Enter <b>Block Title</b> and the <b>Block description</b></li>
            <li style='padding: 3px'>Under the <b>Block body</b> enter the following URL:
                <ol><pre>&lt;a href=&lt;your domain&gt;/moLogin&lt;enter text you want to show on the link&lt;/a&gt;</pre></ol>
                <ol>For example: If your domain name is <b>https://www.miniorange.com</b> then, enter: <b>&lt;a href= 'https://www.miniorange.com/moLogin'> Click here to Login&lt;/a&gt;</b> in the <b>Block body</b> textfield </ol>
            </li>
            <li style='padding: 3px'>From the text filtered dropdown select either <b>Filtered HTML</b> or <b>Full HTML</b></li>
            <li style='padding: 3px'>From the division under <b>REGION SETTINGS</b> select where do you want to show the login link</li>
            <li style='padding: 3px'>Click on the <b>SAVE block</b> button to save your settings</li><br>
            </font>
            </div>
            </div>",

        '#attributes' => array(
            ),
      );

    $form['miniorange_oauth_client_config_submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save'),
        '#id' => 'button_config',
    );
      return $form;
}

public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    global $base_url;
    if(!empty(\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url')))
        $baseUrlValue = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url');
    else
        $baseUrlValue = $base_url;
if(isset($form['miniorange_oauth_client_app']))
 $client_app =  $form['miniorange_oauth_client_app']['#value'];
 if(empty($client_app))
 {
   $client_app =\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_app');
 }

 if(isset($form['miniorange_oauth_app_name']['#value']))
 $app_name = $form['miniorange_oauth_app_name']['#value'];
 if(empty($app_name))
 {
   $client_app = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_app_name');
 }

 if(isset($form['miniorange_oauth_client_display_name']['#value']))
 $display_name = $form['miniorange_oauth_client_display_name'] ['#value'];

 if(isset($form['miniorange_oauth_client_id']))
 $client_id = $form['miniorange_oauth_client_id']['#value'];
 if(empty($client_id))
 {
   $client_id =\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_client_id');
 }

 if(isset($form['miniorange_oauth_client_secret']['#value']))
 $client_secret = $form['miniorange_oauth_client_secret'] ['#value'];

 if(empty($client_secret))
 {
   $client_id = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_client_secret');
 }

 if(isset($form['miniorange_oauth_client_scope']['#value']))
 $scope = $form['miniorange_oauth_client_scope']['#value'];

 if(empty($scope))
 {
   $scope = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_scope');
 }

  if(isset($form['miniorange_oauth_client_authorize_endpoint']['#value']))
 $authorize_endpoint = $form['miniorange_oauth_client_authorize_endpoint'] ['#value'];

 if(empty($authorize_endpoint))
 {
   $authorize_endpoint = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_authorize_endpoint');
 }

 if(isset($form['miniorange_oauth_client_access_token_endpoint']['#value']))
 $access_token_ep = $form['miniorange_oauth_client_access_token_endpoint']['#value'];

 if(empty($access_token_ep))
 {
   $access_token_ep =\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_access_token_ep');
 }

 if(isset($form['miniorange_oauth_client_userinfo_endpoint']['#value']))
 $user_info_ep = $form['miniorange_oauth_client_userinfo_endpoint']['#value'];


 if(empty($user_info_ep))
 {
   $user_info_ep = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_userinfo_endpoint');
 }

 $callback_uri = $baseUrlValue."/mo_login";

 $app_values = array();
 $app_values['client_id'] = $client_id;
 $app_values['client_secret'] = $client_secret;
 $app_values['app_name'] = $app_name;
 $app_values['display_name'] = $display_name;
 $app_values['scope'] = $scope;
 $app_values['authorize_endpoint'] = $authorize_endpoint;
 $app_values['access_token_ep'] = $access_token_ep;
 $app_values['user_info_ep'] = $user_info_ep;
 $app_values['callback_uri'] = $callback_uri;
 $app_values['client_app'] = $client_app;

\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_app',$client_app)->save();

\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_appval',$app_values)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_app_name',$app_name)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_display_name',$display_name)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_client_id',$client_id)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_client_secret',$client_secret)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_scope',$scope)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_authorize_endpoint',$authorize_endpoint)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_access_token_ep',$access_token_ep)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_user_info_ep',$user_info_ep)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_callback_uri',$callback_uri)->save();
 drupal_set_message(t('Configurations saved successfully.'));
}
public function miniorange_oauth_client_test_config(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)
{
  global $base_url;
  if(!empty(\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url')))
    $baseUrlValue = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url');
  else
    $baseUrlValue = $base_url;
    $testUrl = $baseUrlValue.'/testConfig';
    return $testUrl;
}
public function miniorange_oauth_client_save_config(array &$form, \Drupal\Core\Form\FormStateInterface $form_state){
global $base_url;
if(isset($form['miniorange_oauth_client_app']))
 $client_app =  $form['miniorange_oauth_client_app']['#value'];
 if(empty($client_app))
 {
   $client_app =\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_app');
 }

 if(isset($form['miniorange_oauth_app_name']['#value']))
 $app_name = $form['miniorange_oauth_app_name']['#value'];
 if(empty($app_name))
 {
   $client_app = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_app_name');
 }

 if(isset($form['miniorange_oauth_client_display_name']['#value']))
 $display_name = $form['miniorange_oauth_client_display_name'] ['#value'];

 if(isset($form['miniorange_oauth_client_id']))
 $client_id = $form['miniorange_oauth_client_id']['#value'];
 if(empty($client_id))
 {
   $client_id =\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_client_id');
 }

 if(isset($form['miniorange_oauth_client_secret']['#value']))
 $client_secret = $form['miniorange_oauth_client_secret'] ['#value'];

 if(empty($client_secret))
 {
   $client_id = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_client_secret');
 }

 if(isset($form['miniorange_oauth_client_scope']['#value']))
 $scope = $form['miniorange_oauth_client_scope']['#value'];

 if(empty($scope))
 {
   $scope = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_scope');
 }

  if(isset($form['miniorange_oauth_client_authorize_endpoint']['#value']))
 $authorize_endpoint = $form['miniorange_oauth_client_authorize_endpoint'] ['#value'];

 if(empty($authorize_endpoint))
 {
   $authorize_endpoint = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_authorize_endpoint');
 }

 if(isset($form['miniorange_oauth_client_access_token_endpoint']['#value']))
 $access_token_ep = $form['miniorange_oauth_client_access_token_endpoint']['#value'];

 if(empty($access_token_ep))
 {
   $access_token_ep =\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_auth_client_access_token_ep');
 }

 if(isset($form['miniorange_oauth_client_userinfo_endpoint']['#value']))
 $user_info_ep = $form['miniorange_oauth_client_userinfo_endpoint']['#value'];


 if(empty($user_info_ep))
 {
   $user_info_ep = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_userinfo_endpoint');
 }
 if(!empty(\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url')))
    $baseUrlValue = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url');
 else
    $baseUrlValue = $base_url;
 $callback_uri = $baseUrlValue."/mo_login";

 $app_values = array();
 $app_values['client_id'] = $client_id;
 $app_values['client_secret'] = $client_secret;
 $app_values['app_name'] = $app_name;
 $app_values['display_name'] = $display_name;
 $app_values['scope'] = $scope;
 $app_values['authorize_endpoint'] = $authorize_endpoint;
 $app_values['access_token_ep'] = $access_token_ep;
 $app_values['user_info_ep'] = $user_info_ep;
 $app_values['callback_uri'] = $callback_uri;
 $app_values['client_app'] = $client_app;

\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_app',$client_app)->save();

\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_appval',$app_values)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_app_name',$app_name)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_display_name',$display_name)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_client_id',$client_id)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_client_secret',$client_secret)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_scope',$scope)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_authorize_endpoint',$authorize_endpoint)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_access_token_ep',$access_token_ep)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_user_info_ep',$user_info_ep)->save();
\Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_auth_client_callback_uri',$callback_uri)->save();
 drupal_set_message(t('Configurations saved successfully.'));
}

public function getTestUrl() {

    global $base_url;
    if(!empty(\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url')))
        $baseUrlValue = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url');
    else
        $baseUrlValue = $base_url;
    $testUrl = $baseUrlValue.'/testConfig';
    return $testUrl;
   }
}