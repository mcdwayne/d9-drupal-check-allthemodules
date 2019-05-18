<?php

/**
 * @file
 * Contains \Drupal\miniorange_saml_idp\Form\MiniorangeIDPSetup.
 */

namespace Drupal\miniorange_saml_idp\Form;
use Drupal\Core\Form\FormBase;
use Drupal\miniorange_saml_idp\Utilities;
use Drupal\miniorange_saml_idp\mo_saml_visualTour;

class MiniorangeIDPSetup extends FormBase {

  public function getFormId() {
    return 'miniorange_saml_idp_setup';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state)
  {
      global $base_url;
      $disable = !Utilities::isCustomerRegistered();
      $moTour = mo_saml_visualTour::genArray();
      $form['tourArray'] = array(
          '#type' => 'hidden',
          '#value' => $moTour,
      );

      $form['markup_idp_header'] = array(
          '#attached' => array(
              'library' => 'miniorange_saml_idp/miniorange_saml_idp.Vtour',
          ),
          '#markup' => '<div class="mo_saml_table_layout_1"><div class="mo_saml_table_layout container"><h2>Configure Identity Provider &nbsp;&nbsp; <a id="Restart_moTour" class="btn btn-danger btn-sm" onclick="Restart_moTour()">Take a Tour</a></h2><hr>',
      );

      if($disable){
          $form['markup_top_register_message'] = array(
              '#markup' => '<div class="mo_saml_register_message">Register/login with miniOrange to enable plugin.</div>'
          );
      }

      $form['markup_idp_note'] = array(
        '#markup' => '<br><div class = "mo_saml_highlight_background_note"><b>Note: </b>Please note down the following information from your Service Provider'
      . ' and keep it handy to configure your Identity Provider.</div><br>',
      );

      $form['markup_idp_list'] = array(
            '#markup' => '<b><ol><li>SP Entity ID / Issuer</li>'
          . ' <li>ACS URL</li>'
          . ' <li>X.509 Certificate for Signing if you are using HTTP-POST Binding. [This is a'
          . ' <a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/licensing">Premium</a> feature]</li>'
          . ' <li>X.509 Certificate for Encryption. [This is a'
          . ' <a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/licensing">Premium</a> feature]</li>'
          . ' <li>NameID Format</li></ol></b><br />',
      );
        $form['markup_saml_idp_disabled'] = array(
          '#markup' => '<div>',
        );

      $form['miniorange_saml_idp_name'] = array(
        '#type' => 'textfield',
        '#title' => t('Service Provider Name*'),
        '#default_value' => \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_name'),
        '#attributes' => array(
              'style' => 'width:700px',
              'placeholder' => 'Service Provider Name'
        ),
        '#disabled' => $disable,
      );

      $form['miniorange_saml_idp_entity_id'] = array(
        '#type' => 'textfield',
        '#title' => t('SP Entity ID or Issuer*'),
        '#description' => t('<b>Note :</b> You can find the EntityID in Your SP-Metadata XML file enclosed in <code>EntityDescriptor</code> tag having attribute as <code>entityID</code>.'),
        '#default_value' => \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_entity_id'),
        '#attributes' => array('style' => 'width:700px','placeholder' => 'SP Entity ID or Issuer'),
        '#disabled' => $disable,
      );

      $form['miniorange_saml_idp_nameid_format'] = array(
        '#type' => 'select',
        '#title' => t('NameID Format:'),
        '#options' => array(
            '1.1:nameid-format:emailAddress' => t('urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'),
            '1.1:nameid-format:unspecified' => t('urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified'),
            '2.0:nameid-format:transient' => t('urn:oasis:names:tc:SAML:1.1:nameid-format:transient'),
            '2.0:nameid-format:persistent' => t('urn:oasis:names:tc:SAML:1.1:nameid-format:persistent'),
         ),
        '#default_value' =>\Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_nameid_format'),
        '#attributes' => array('style' => 'width:700px'),
        '#description' => t('(<b>NOTE:</b> urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress is selected by default)'),
      );

      $form['miniorange_saml_idp_acs_url'] = array(
        '#type' => 'textfield',
        '#title' => t('ACS URL*'),
        '#description' => t('<b>Note :</b> You can find the SAML Login URL in Your SP-Metadata XML file enclosed in <code>AssertionConsumerService </code> tag <br/>having attribute as <code>Location</code>.'),
        '#default_value' => \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_acs_url'),
        '#attributes' => array('style' => 'width:700px','placeholder' => 'ACS URL'),
        '#disabled' => $disable,
      );

      $form['miniorange_saml_idp_relay_state'] = array(
        '#type' => 'textfield',
        '#title' => t('Relay State'),
        '#default_value' => \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_relay_state'),
        '#attributes' => array('style' => 'width:700px','placeholder' => 'Relay State (optional)'),
        '#disabled' => $disable,
      );

      $form['miniorange_saml_idp_x509_certificate_request'] = array(
         '#type' => 'textarea',
         '#title' => t('x.509 Certificate Value  <b>[Note: For Signed Request.] </b><a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/licensing"> [Premium]</a>'),
         '#cols' => '10',
         '#rows' => '5',
         '#attributes' => array('style' => 'width:700px','placeholder' => 'Copy and Paste the content from the downloaded certificate or copy the content enclosed in X509Certificate tag (has parent tag KeyDescriptor use=signing) in SP-Metadata XML file)'),
         '#disabled' => TRUE,
      );

      $form['markup_1'] = array(
         '#markup' => '<b>NOTE:</b> Format of the certificate:<br><b>-----BEGIN CERTIFICATE-----<br>'
          .'XXXXXXXXXXXXXXXXXXXXXXXXXXX<br>-----END CERTIFICATE-----</b>'
      );

      $form['miniorange_saml_idp_x509_certificate_assertion'] = array(
          '#type' => 'textarea',
          '#title' => t('x.509 Certificate Value <b>[Note: For Encrypted Assertion.]</b> <a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/licensing"> [Premium]</a>'),
          '#cols' => '10',
          '#rows' => '5',
          '#attributes' => array('style' => 'width:700px','placeholder' => 'Copy and Paste the content from the downloaded certificate or copy the content enclosed in X509Certificate tag (has parent tag KeyDescriptor use=encryption)'),
          '#disabled' => TRUE,
      );

      $form['markup_2'] = array(
          '#markup' => '<b>NOTE:</b> Format of the certificate:<br><b>-----BEGIN CERTIFICATE-----<br>'
           .'XXXXXXXXXXXXXXXXXXXXXXXXXXX<br>-----END CERTIFICATE-----</b>'
      );

      $form['miniorange_saml_idp_response_signed'] = array(
        '#markup' => '<br>',
        '#type' => 'checkbox',
        '#title' => t('<b>Response Signed:</b> This is a <b>Premium</b> feature.'
        . ' Check <a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/licensing"><b>Licensing</b></a>'
        . ' Tab to learn more.'),
        '#disabled' => TRUE,
      );


      $form['miniorange_saml_idp_encrypt_signed'] = array(
        '#type' => 'checkbox',
        '#title' => t('<b>Encrypted Assertion:</b> This is a <b>Premium</b> feature.'
        . ' Check <a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/licensing"><b>Licensing</b></a>'
        . ' Tab to learn more.'),
        '#disabled' => TRUE,
      );
      $form['miniorange_saml_idp_assertion_signed_start'] = array(
          '#markup' => '<div id="assertion_signed">'
      );

      $form['miniorange_saml_idp_assertion_signed'] = array(
        '#type' => 'checkbox',
        '#title' => t('<b>Assertion Signed</b> (Check If you want to sign SAML Assertion.)'),
        '#default_value' => \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_assertion_signed'),
        '#disabled' => $disable,
      );

      $form['miniorange_saml_idp_buttons'] = array(
        '#markup' => '</div><br><br>'
      );

      $form['miniorange_saml_idp_config_submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save Configuration'),
        '#disabled' => $disable,
      );

      $form['miniorange_saml_idp_config_delete'] = array(
         '#type' => 'submit',
         '#value' => t('Delete Configuration'),
         '#submit' => array('::miniorange_saml_idp_delete_idp_config'),
         '#disabled' => $disable,
      );
      $disable_true="";
      if($disable == TRUE){
          $disable_true = 'disabled="True"';
      }
      $testConfigUrl = "\'" . $this->getTestUrl() . "\'";
      $form['miniorange_saml_idp_test_config_button'] = array (
          '#attached' => array(
             'library' => 'miniorange_saml_idp/miniorange_saml_idp.test',
         ),
          '#markup' => '<a '.$disable_true.' id="testConfigButton" class="btn btn-primary btn-sm" style="padding:6px 12px;" onclick="testIdpConfig($testConfigUrl);"><b>Test Configuration</b></a><br><br></div></div>',
      );

      Utilities::AddSupportForm($form, $form_state);

  return $form;
 }

 function getTestUrl() {
  global $base_url;
  $testUrl = $base_url . '/?q=testConfig';
  return $testUrl; 
 }

 
 function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)
 {
     if (isset($_POST['value_check']) && $_POST['value_check'] == 'True') {

         \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_name','')->save();
         \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_entity_id','')->save();
         \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_acs_url','')->save();
         \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_relay_state','')->save();
         \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_nameid_format','')->save();
         \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_assertion_signed','')->save();
         drupal_set_message(t('Your Service Provider Configuration is successfully deleted.'));
         $_POST['value_check'] = 'False';
     } else {
         global $base_url;
         $sp_name = $form['miniorange_saml_idp_name']['#value'];
         $issuer = $form['miniorange_saml_idp_entity_id']['#value'];
         $acs_url = $form['miniorange_saml_idp_acs_url']['#value'];
         $relay_state = $form['miniorange_saml_idp_relay_state']['#value'];
         $nameid_format = $form['miniorange_saml_idp_nameid_format']['#value'];
         $is_assertion_signed = $form['miniorange_saml_idp_assertion_signed']['#value'] == 1 ? TRUE : FALSE;

         if(empty($sp_name)){
             drupal_set_message(t('Please  Enter <b><u>Service Provider Name</u></b>. The <i><u>Service Provider Name</u></i>, <i><u>SP Entity ID or Issuer</u></i> and <i><u>ACS URL</u></i> fields are mandatory.'), 'error');
             return;
         } elseif(empty($issuer)){
             drupal_set_message(t('Please  Enter <b><u>SP Entity ID or Issuer</u></b>. The <i><u>Service Provider Name</u></i>, <i><u>SP Entity ID or Issuer</u></i> and <i><u>ACS URL</u></i> fields are mandatory.'), 'error');
             return;
         } elseif(empty($acs_url)){
             drupal_set_message(t('Please  Enter <b><u>ACS URL</u></b>. The <i><u>Service Provider Name</u></i>, <i><u>SP Entity ID or Issuer</u></i> and <i><u>ACS URL</u></i> fields are mandatory.'), 'error');
             return;
         }

         \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_name', $sp_name)->save();
         \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_entity_id', $issuer)->save();
         \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_acs_url', $acs_url)->save();
         \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_relay_state', $relay_state)->save();
         \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_nameid_format', $nameid_format)->save();
         \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_assertion_signed', $is_assertion_signed)->save();
         drupal_set_message(t('Your Service Provider Configuration are successfully saved. You can click on Test Configuration button below to test these configurations.'));
     }
 }


   function saved_support($form, &$form_state)
   {
        $email = $form['miniorange_saml_email_address_support']['#value'];
        $phone = $form['miniorange_saml_phone_number_support']['#value'];
        $query = $form['miniorange_saml_support_query_support']['#value'];
        Utilities::send_support_query($email, $phone, $query);
   }

    function miniorange_saml_idp_delete_idp_config(&$form, $form_state)
    {
        $myArray = array();
        $myArray = $_POST;
        $form_id = $_POST['form_id'];
        $form_token = $_POST['form_token'];
        $op = $_POST['op'];
        $build_id = $_POST['form_build_id'];

        ?>

        <html>
        <head><title>Confirmation</title><link href="https://fonts.googleapis.com/css?family=PT+Serif" rel="stylesheet"></head>
        <body style="font-family: 'PT Serif', serif;">
        <div style="margin: 15% auto; height:35%; width: 40%; background-color: #eaebed; text-align: center; box-shadow: 10px 5px 5px darkgray; border-radius: 2%;">
            <div style="color: #a94442; background-color:#f2dede; padding: 15px; margin-bottom: 20px; text-align:center; border:1px solid #E6B3B2; font-size:16pt; border-radius: 2%;">
                <strong>Are you sure you want to delete configuratiuon..!!</strong>
            </div>
            <p style="font-size:14px; margin-left: 8%; margin-right: 8%"><strong>Warning </strong>: Your SP configuration will be deleted forever. Are you sure you want to delete SP configuration:</p><br/>
            <form name="f" method="post" action="" id="mo_remove_account">
                <div>
                    <input type="hidden" name="op" value=<?php echo $op;?>>
                    <input type="hidden" name="form_build_id" value= <?php echo $build_id;?>>
                    <input type="hidden" name="form_token" value=<?php echo $form_token;?>>
                    <input type="hidden" name="form_id" value= <?php echo $form_id;?>>
                    <input type="hidden" name="value_check" value= 'True'>
                </div>
                <div style="margin: auto; text-align: center;"   class="mo2f_modal-footer">
                    <input type="submit" style=" padding:1%; width:100px; background: #0091CD none repeat scroll 0% 0%; cursor: pointer; font-size:15px; border-width: 1px; border-style: solid; border-radius: 3px; white-space: nowrap; box-sizing: border-box;border-color: #0073AA; box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset; color: #FFF;"
                           name="miniorange_confirm_submit" class="button button-danger button-large" value="Confirm"/>
                </div>
            </form>
        </div>
        </body>
        </html>
        <?php
        exit;
    }
}