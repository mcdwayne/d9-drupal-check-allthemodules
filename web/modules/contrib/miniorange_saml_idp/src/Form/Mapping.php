<?php
/**
 * @file
 * Contains Attribute for miniOrange SAML IDP Module.
 */

 /**
 * Showing Settings form.
 */
namespace Drupal\miniorange_saml_idp\Form;
use Drupal\Core\Form\FormBase;
use Drupal\miniorange_saml_idp\Utilities;
use Drupal\miniorange_saml_idp\mo_saml_visualTour;

class Mapping extends FormBase {
	 
  public function getFormId() {
    return 'miniorange_saml_mapping';
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

      $form['header_top_style_1'] = array(
          '#markup' => '<div class="mo_saml_table_layout_1">',
      );

      $form['markup_idp_attr_header'] = array(
          '#attached' => array(
              'library' => 'miniorange_saml_idp/miniorange_saml_idp.Vtour',
          ),
          '#markup' => '<div class="mo_saml_table_layout container"><h3>Attribute Mapping (Optional) &nbsp;&nbsp; <a id="Restart_moTour" class="btn btn-danger btn-sm" onclick="Restart_moTour()">Take a Tour</a></h3><hr><br>',
      );

      $form['miniorange_saml_idp_nameid_attr_map'] = array(
        '#type' => 'select',
        '#title' => t('NameID Attribute:'),
        '#options' => array(
            'emailAddress' => t('Drupal Email Address'),
            'username' => t('Drupal Username'),),
        '#default_value' =>\Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_nameid_attr_map'),
        '#attributes' => array('style' => 'width:700px'),
      );

     $form['markup_idp_sp_note'] = array(
         '#markup' => '<div class = "mo_saml_highlight_background_note" span style="color:red"><b>Note:</b></divspan> This attribute value is sent in SAML Response. Users in your Service Provider
         will be searched (existing users) or created (new users) based on this attribute. Use <b>EmailAddress</b> by default.</b></div>',
     );

     $form['markup_idp_attr_header2'] = array(
         '#markup' => '<br/><br/><div id="Custom_Attribute_Mapping_start"><h3>Custom Attribute Mapping <a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/licensing">[Premium]</a></h3>'
     );

     $form['miniorange_saml_idp_attr1_name'] = array(
         '#type' => 'textfield',
         '#title' => t('Attribute Name 1'),
         '#default_value' => \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_attr1_name'),
         '#attributes' => array('style' => 'width:700px','placeholder' => 'Enter Attribute Name'),
         '#required' => FALSE,
         '#disabled' => TRUE,
     );

     $form['miniorange_saml_idp_attr1_value'] = array(
         '#type' => 'select',
         '#title' => t('Attribute Value'),
         '#disabled' => TRUE,
         '#options' => array(
             '' => t('Select Attribute Value'),
             'mail' => t('Email Address'),
             'name' => t('Username'),
             'roles' => t('User Roles'),
         ),
         '#attributes' => array('style' => 'width:700px'),
         '#default_value' =>\Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_attr1_value'),
     );

     $form['miniorange_saml_idp_attr2_name'] = array(
         '#type' => 'textfield',
         '#title' => t('Attribute Name 2'),
         '#default_value' =>\Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_attr2_name'),
         '#attributes' => array('style' => 'width:700px','placeholder' => 'Enter Attribute Name'),
         '#required' => FALSE,
         '#disabled' => TRUE,
     );

     $form['miniorange_saml_idp_attr2_value'] = array(
         '#type' => 'select',
         '#title' => t('Attribute Value'),
         '#disabled' => TRUE,
         '#options' => array(
             '' => t('Select Attribute Value'),
             'mail' => t('Email Address'),
             'name' => t('Username'),
             'roles' => t('User Roles'),
         ),
         '#attributes' => array('style' => 'width:700px'),
         '#default_value' =>\Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_attr2_value'),
     );

     $form['miniorange_saml_idp_attr3_name'] = array(
         '#type' => 'textfield',
         '#title' => t('Attribute Name 3'),
         '#default_value' => \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_attr3_name'),
         '#attributes' => array('style' => 'width:700px','placeholder' => 'Enter Attribute Name'),
         '#required' => FALSE,
         '#disabled' => TRUE,
     );

     $form['miniorange_saml_idp_attr3_value'] = array(
         '#type' => 'select',
         '#title' => t('Attribute Value'),
         '#disabled' => TRUE,
         '#options' => array(
             '' => t('Select Attribute Value'),
             'mail' => t('Email Address'),
             'name' => t('Username'),
             'roles' => t('User Roles'),
         ),
         '#attributes' => array('style' => 'width:700px'),
         '#default_value' =>  \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_attr3_value'),
     );

     $form['Custom_Attribute_Mapping_end'] = array(
         '#markup' => '</div>'
     );

     $form['miniorange_saml_idp_additional_user_attrs'] = array(
         '#attached' => array('library' => 'miniorange_saml_idp/miniorange_saml_idp.attributes',),
         '#markup' => '<br /><h3>Additional User Attributes(Optional) &nbsp;&nbsp;&nbsp;&nbsp;
                            <a class="btn btn-primary btn-sm" style="padding:6px 12px;" onclick=";">+</a>&nbsp;&nbsp;&nbsp;&nbsp;
                            <a class="btn btn-primary btn-sm" style="padding:6px 12px;" onclick=";">-</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="' . $base_url . '/admin/config/people/miniorange_saml_idp/licensing">[Premium]</a><br></h3>'
     );

     $form['markup_idp_user_attr_note'] = array(
         '#markup' => '<div id = "attrNote" class="messages status">User Profile Attribute Name: It is the name which you want to send to your SP. It should be unique.<br />User Profile Attribute Value: It is the user attribute (machine name) whose value you want to send to SP.</div>',
     );

     $form['user_profile_attr_name_1'] = array(
         '#type' => 'textfield',
         '#title' => 'User Profile Attribute Name',
         '#attributes' => array('style' => 'width:700px'),
         '#disabled' => TRUE,

     );
     $form['user_profile_attr_value_1'] = array(
         '#type' => 'textfield',
         '#title' => 'User Profile Attribute Value',
         '#attributes' => array('style' => 'width:700px'),
         '#disabled' => TRUE,
     );

     $form['miniorange_saml_idp_attr_map_submit'] = array(
         '#type' => 'submit',
         '#value' => t('Save'),
         '#disabled' => $disable,
     );

     $form['miniorange_saml_idp_layout_div_close'] = array(
         '#markup'=>'</div>',
     );

     Utilities::AddSupportForm($form, $form_state);

  return $form;

 }
 
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)
  {
      $nameid_attr = $form['miniorange_saml_idp_nameid_attr_map']['#value'];
      if($nameid_attr == ''){
        $nameid_attr = 'emailAddress';
      }

      \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('miniorange_saml_idp_nameid_attr_map', $nameid_attr)->save();
      drupal_set_message(t('Your settings are saved successfully.'));
  }

  function saved_support($form, &$form_state)
  {
       $email = $form['miniorange_saml_email_address_support']['#value'];
       $phone = $form['miniorange_saml_phone_number_support']['#value'];
       $query = $form['miniorange_saml_support_query_support']['#value'];
       Utilities::send_support_query($email, $phone, $query);
  }
}