<?php
/**
 * @file
 * Contains Attribute and Role Mapping for miniOrange SAML Login Module.
 */

 /**
 * Showing Settings form.
 */
namespace Drupal\miniorange_saml\Form;

use Drupal\Core\Form\FormBase;
use Drupal\miniorange_saml\Utilities;
use Drupal\miniorange_saml\mo_saml_visualTour;

class Mapping extends FormBase {
	 
  public function getFormId() {
    return 'miniorange_saml_mapping';
  }
	 
 public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

  global $base_url;

     Utilities::visual_tour_start($form, $form_state);

     $form['markup_top'] = array(
         '#markup' => '<div class="mo_saml_table_layout_1"><div class="mo_saml_table_layout mo_saml_container">'
     );

     $form['markup_idp_attr_header'] = array(
         '#attached' => array(
             'library' => 'miniorange_saml/miniorange_saml.Vtour',
         ),
         '#markup' => '<h3>ATTRIBUTE MAPPING (OPTIONAL) &nbsp;&nbsp; <a id="Restart_moTour" class="btn btn-danger btn-sm" onclick="Restart_moTour()">Take a Tour</a></h3><hr><br>',
     );

     $form['miniorange_saml_account_username_div'] = array(
         '#markup'=>'<div id = "mo_saml_username_div_start">'
     );

     $form['miniorange_saml_account_username_by'] = array(
        '#type' => 'select',
        '#title' => t('Login/Create Drupal account by'),
            '#options' => array(
                1 => t('Drupal Email Address'),
                2 => t('Drupal Username'),
            ),
        '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_login_by'),
        '#attributes' => array('style' => 'width:700px;background-image: inherit;background-color: white;-webkit-appearance: menulist;'),
     );

     $form['miniorange_saml_account_username_div_end'] = array(
         '#markup'=>'</div>'
     );

     $form['Configure_Attribute_Mapping'] = array(
        '#markup' => '<div id="configure_attribute_mapping_start">'
     );

  $form['miniorange_saml_username_attribute'] = array(
    '#type' => 'textfield',
    '#title' => t('Username Attribute'),
    '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_username_attribute'),
      '#attributes' => array('style' => 'width:700px; background-color: hsla(0,0%,0%,0.08) !important;','placeholder' => 'Enter Username attribute'),
	'#disabled' => TRUE,
  );

  $form['miniorange_saml_email_attribute'] = array(
    '#type' => 'textfield',
    '#title' => t('Email Attribute'),
    '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_email_attribute'),
      '#attributes' => array('style' => 'width:700px; background-color: hsla(0,0%,0%,0.08) !important;','placeholder' => 'Enter Email attribute'),
	'#disabled' => TRUE,
  );
  
  $form['miniorange_saml_idp_attr1_name'] = array(
	'#type' => 'textfield',
	'#title' => t('Role'),
	'#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_attr1_name'),
    '#attributes' => array('style' => 'width:700px; background-color: hsla(0,0%,0%,0.08) !important;','placeholder' => 'Enter Role Attribute'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );

  $form['Configure_Attribute_Mapping_End'] = array(
    '#markup' => '</div><div class="mo_saml_highlight_background_note"><b>NOTE: Username Attribute</b>, <b>Email Attribute</b> and <b>Role</b> are configurable in <b>
        <a href="' . $base_url . '/admin/config/people/miniorange_saml/Licensing">Standard, Premium, Enterprise</a></b> versions of the module.</div>',
  );

     $form['Enable_Rolemapping_Start'] = array(
         '#markup' => '<br><div id="Enable_Rolemapping">',
     );

     $form['markup_role'] = array(
         '#markup' => '<br><h3>ROLE MAPPING</h3><hr><br>',
     );


     $form['miniorange_saml_enable_rolemapping'] = array(
         '#type' => 'checkbox',
         '#title' => t('Check this option if you want to <b>enable Role Mapping</b>'),
         '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_enable_rolemapping'),
         '#attributes' => array('class="mo_saml_checkbox"'),
     );

     $form['Enable_Rolemapping_End'] = array(
         '#markup' => '</div>',
     );

     $mrole = user_role_names(TRUE);
     $def_role = \Drupal::configFactory()->getEditable('miniorange_saml.settings')->get('miniorange_saml_def_role');

     $form['Default_Mapping_Start'] = array(
         '#markup' => '<div id="Default_Mapping">',
     );

     $form['miniorange_saml_default_mapping'] = array(
         '#type' => 'select',
         '#title' => t('Select default group for the new users'),
         '#options' => array_keys($mrole),
         '#default_value' => $def_role,
         '#attributes' => array('style' => 'width:700px;background-image: inherit;background-color: white;-webkit-appearance: menulist;'),
     );

     $form['Default_Mapping_End'] = array(
         '#markup' => '</div>',
     );

     $form['miniorange_saml_prem_features'] = array(
         '#markup'=>'<div class="mo_saml_highlight_background_note_1">'
     );

     $form['miniorange_saml_disable_role_update'] = array(
         '#type' => 'checkbox',
         '#title' => t('Check this option if you do not want to update user role if roles not mapped '),
         '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_disable_role_update'),
         '#disabled' => TRUE,
         '#attributes' => array('-webkit-appearance: none;background-color: #9E9E9E !important;'),
         '#description'=>t('This feature is availabe in <b><a href="' . $base_url . '/admin/config/people/miniorange_saml/Licensing">Premium, Enterprise</a></b> versions of the module.'),
     );

     $form['miniorange_saml_disable_autocreate_users'] = array(
         '#type' => 'checkbox',
         '#title' => t('Check this option if you want to disable <b>auto creation</b> of users if user does not exist.'),
         '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_disable_autocreate_users'),
         '#disabled' => TRUE,
         '#description'=>t('This feature is availabe in <b><a href="' . $base_url . '/admin/config/people/miniorange_saml/Licensing">Premium, Enterprise</a></b> versions of the module.'),
     );

     $form['miniorange_saml_prem_features_end'] = array(
         '#markup'=>'</div>'
     );

  $form['markup_cam_attr'] = array(
    '#markup' => '<br><br><h3>CUSTOM ATTRIBUTE MAPPING </h3><hr><br><div class="mo_saml_highlight_background_note_1"><b>NOTE : Custom Attribute Mapping</b> are configurable in <b>
        <a href="' . $base_url . '/admin/config/people/miniorange_saml/Licensing">Standard, Premium, Enterprise</a></b> versions of the module.</div>',
  );

  $form['markup_cam'] = array(
     '#markup' => '<br><div><p><b>NOTE: </b> Add the Drupal field attributes in the Attribute Name textfield and add the IdP attibutes that you need to map with the drupal attributes in the IdP Attribute Name textfield. 
                               <br> <b>Attribute Name:</b> It is the user attribute (machine name) whose value you want to set in site.
                               <br> <b>IdP Attribute Name:</b> It is the name which you want to get from your IDP. It should be unique.</p></div><p><b>For example: If the attribute name in the drupal is name then its machine name will be field_name.</b></p>',
  );

  $form['Custom_Attribute_Mapping_Start'] = array(
    '#markup' => '<div id="Custom_Attribute_Mapping">',
  );
   $form['miniorange_saml_attr5_name'] = array(
	'#type' => 'textfield',
	'#title' => t('Attribute Name 1 '),
	'#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_attr5_name'),
       '#attributes' => array('style' => 'width:700px;background-color: hsla(0,0%,0%,0.08) !important;','placeholder' => 'Enter Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );
  
  $form['miniorange_saml_idp_attr5_name'] = array(
	'#type' => 'textfield',
	'#title' => t('IdP Attribute Name 1'),
	'#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_attr5_name'),
      '#attributes' => array('style' => 'width:700px;background-color: hsla(0,0%,0%,0.08) !important;','placeholder' => 'Enter IdP Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );
  
  $form['miniorange_saml_attr2_name'] = array(
	'#type' => 'textfield',
	'#title' => t('Attribute Name 2'),
	'#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_attr2_name'),
      '#attributes' => array('style' => 'width:700px;background-color: hsla(0,0%,0%,0.08) !important;','placeholder' => 'Enter Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );
  
  $form['miniorange_saml_idp_attr2_name'] = array(
	'#type' => 'textfield',
	'#title' => t('IdP Attribute Name 2'),
	'#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_attr2_name'),
      '#attributes' => array('style' => 'width:700px;background-color: hsla(0,0%,0%,0.08) !important;','placeholder' => 'Enter IdP Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );
  
  $form['miniorange_saml_attr3_name'] = array(
	'#type' => 'textfield',
	'#title' => t('Attribute Name 3'),
	'#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_attr3_name'),
      '#attributes' => array('style' => 'width:700px;background-color: hsla(0,0%,0%,0.08) !important;','placeholder' => 'Enter Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );
  
  $form['miniorange_saml_idp_attr3_name'] = array(
	'#type' => 'textfield',
	'#title' => t('IdP Attribute Name 3'),
	'#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_attr3_name'),
      '#attributes' => array('style' => 'width:700px;background-color: hsla(0,0%,0%,0.08) !important;','placeholder' => 'Enter IdP Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );
  
  $form['miniorange_saml_attr4_name'] = array(
	'#type' => 'textfield',
	'#title' => t('Attribute Name 4'),
	'#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_attr4_name'),
      '#attributes' => array('style' => 'width:700px;background-color: hsla(0,0%,0%,0.08) !important;','placeholder' => 'Enter Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );
  
  $form['miniorange_saml_idp_attr4_name'] = array(
	'#type' => 'textfield',
	'#title' => t('IdP Attribute Name 4'),
	'#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_attr4_name'),
      '#attributes' => array('style' => 'width:700px;background-color: hsla(0,0%,0%,0.08) !important;','placeholder' => 'Enter IdP Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );
  $form['Custom_Attribute_Mapping_End'] = array(
     '#markup' => '</div>',
  );

  $form['markup_custom_role_mapping'] = array(
     '#markup' => '<br/><h3>CUSTOM ROLE MAPPING </h3><hr><br><div class="mo_saml_highlight_background_note_1">
                   <b>NOTE : Custom Role Mapping</b> is configurable in <b><a href="' . $base_url . '/admin/config/people/miniorange_saml/Licensing">Premium, Enterprise</a></b> versions of the module.</div>',
  );
  
  foreach($mrole as $roles) {
    $rolelabel = str_replace(' ','',$roles);
    $form['miniorange_saml_role_' . $rolelabel] = array(
	    '#type' => 'textfield',
	    '#title' => t($roles),
	    '#maxlength' => 255,
	    '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_role_' . $rolelabel),
        '#attributes' => array('style' => 'width:700px;background-color: hsla(0,0%,0%,0.08) !important;','placeholder' => 'Semi-colon(;) separated Group/Role value for ' . $roles),
	    '#required' => FALSE,
	    '#disabled' => TRUE,
    );
  }
   
  $form['miniorange_saml_gateway_config_submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save Configuration'),
      '#attributes' => array('style' => 'background: #337ab7;color: #ffffff;text-shadow: 0 -1px 1px #337ab7, 1px 0 1px #337ab7, 0 1px 1px #337ab7, -1px 0 1px #337ab7;box-shadow: 0 1px 0 #337ab7;border-color: #337ab7 #337ab7 #337ab7;'),
    '#prefix' => '<br>'
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
    $mrole = user_role_names(TRUE);
    $login_by = $form['miniorange_saml_account_username_by']['#value'];
    $username_attribute = $form['miniorange_saml_username_attribute']['#value'];
    $email_attribute = $form['miniorange_saml_email_attribute']['#value'];
    $default_mapping= $form['miniorange_saml_default_mapping']['#value'];
    $enable_rolemapping = $form['miniorange_saml_enable_rolemapping']['#value'];
    $i = 0;
    foreach($mrole as $key => $value) {
		$def_role[$i] = $value;
        $i++;
    }
	
    if($enable_rolemapping == 1) {
		$enable_rolemapping = TRUE;
	}
	else {
		$enable_rolemapping = FALSE;
	}
	
	if($enable_rolemapping) {
		\Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_default_role', $def_role[$default_mapping]);
		\Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_def_role', $default_mapping);
	}
	else {
	 \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_default_role', $mrole['authenticated'])->save();
	}
 
  \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_login_by', $login_by)->save();
  \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_username_attribute', $username_attribute)->save();
  \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_email_attribute', $email_attribute)->save();
  \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_enable_rolemapping', $enable_rolemapping)->save();
  drupal_set_message(t('Signin Settings successfully saved'));
  }
  }