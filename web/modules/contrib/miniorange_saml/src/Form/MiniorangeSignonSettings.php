<?php
/**
 * @file
 * Contains Login Settings for miniOrange SAML Login Module.
 */

 /**
 * Showing Settings form.
 */
 namespace Drupal\miniorange_saml\Form;
 
 use Drupal\Core\Form\FormBase;
 use Drupal\miniorange_saml\Utilities;
 use Drupal\miniorange_saml\mo_saml_visualTour;

 class MiniorangeSignonSettings extends FormBase {
	 
  public function getFormId() {
    return 'miniorange_saml_login_setting';
  }
  
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

  global $base_url;

  $form['markup_top'] = array(
     '#markup' => '<div class="mo_saml_table_layout_1"><div id="signon_settings_tab" class="mo_saml_table_layout mo_saml_container">'
  );

      Utilities::visual_tour_start($form, $form_state);

  $form['markup_1'] = array(
      '#attached' => array(
          'library' => 'miniorange_saml/miniorange_saml.Vtour',
      ),
      '#markup' => '<h3>SIGN IN SETTINGS &nbsp;&nbsp; <a id="Restart_moTour" class="btn btn-danger btn-sm" onclick="Restart_moTour()">Take a Tour</a></h3><hr><br/>',
  );

  $form['markup_prem_plans'] = array(
      '#markup' => '<div class="mo_saml_highlight_background_note">Available in <b><a href="' . $base_url . '/admin/config/people/miniorange_saml/Licensing">Standard, Premium, Enterprise</a></b> versions of the module</div><br>',
  );

  $form['miniorange_saml_force_auth'] = array(
    '#type' => 'checkbox',
    '#title' => t('Protect website against anonymous access'),
    '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_force_auth'),
    '#disabled' => TRUE,
    '#description' => t('<b>Note: </b>Users will be redirected to your IdP for login in case user is not logged in and tries to access website.<br><br>'),
  );

  $form['miniorange_saml_auto_redirect'] = array(
    '#type' => 'checkbox',
    '#title' => t('Check this option if you want to <b>auto redirect the user to IdP.</b>'),
    '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_auto_redirect_to_idp'),
	'#disabled' => TRUE,
    '#description' => t('<b>Note:</b> Users will be redirected to your IdP for login when the login page is accessed.<br><br>'),
  );


  $form['miniorange_saml_enable_backdoor'] = array(
    '#type' => 'checkbox',
    '#title' => t('Check this option if you want to enable <b>backdoor login.</b>'),
    '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_enable_backdoor'),
    '#disabled' => TRUE,
    '#description' => t('<b>Note: </b>Checking this option <b>creates a backdoor to login to your Website using Drupal credentials</b><br>'
          . ' incase you get locked out of your IdP. Note down this URL: <a><code>We provide backdoor URL in standard module.</code></a><br><br>'),
  );
  
  $form['miniorange_saml_default_relaystate'] = array(
    '#type' => 'textfield',
    '#title' => t('Default Redirect URL after login.'),
    '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_default_relaystate'),
      '#attributes' => array('style' => 'width:700px','placeholder' => 'Enter Default Redirect URL'),
	'#disabled' => TRUE,
  );
    
  $form['miniorange_saml_gateway_config_submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save Configuration'),
      '#disabled' => TRUE,
  );

  $form['main_layout_div_end'] = array(
    '#markup' => '<br><br></div>',
  );

  Utilities::AddsupportTab( $form, $form_state);
  
  return $form;

  }
  function saved_support($form, &$form_state)
  {
     $email = $form['miniorange_saml_email_address_support']['#value'];
     $phone = $form['miniorange_saml_phone_number_support']['#value'];
     $query = $form['miniorange_saml_support_query_support']['#value'];
     Utilities::send_support_query($email, $phone, $query);
  }
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

  }

 }