<?php

/**
 * @file
 * Contains \Drupal\miniorange_oauth_client\Form\MiniorangeGeneralSettings.
 */

namespace Drupal\miniorange_oauth_client\Form;

use Drupal\Core\Form\FormBase;
use Drupal\miniorange_oauth_client\MiniorangeOAuthClientSupport;

class MiniorangeMapping extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_mapping';
  }
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state)
  {

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

      $form['markup_library'] = array(
        '#attached' => array(
            'library' => array(
                "miniorange_oauth_client/miniorange_oauth_client.admin",
            )
        ),
      );

    $email_attr = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_email_attr_val');
    $name_attr =\Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_name_attr_val');
    $baseUrlValue = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_base_url');

    $form['miniorange_oauth_client_base_url'] = array(
      '#type' => 'textfield',
      '#id' => 'text_field',
      '#title' => t('Base URl: '),
      '#disabled' => \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_disabled'),
      '#default_value' => $baseUrlValue,
      '#attributes' => array(
      ),
    );

    $form['miniorange_oauth_client_email_attr'] = array(
    '#type' => 'textfield',
    '#id' => 'text_field',
    '#title' => t('Email Attribute: '),
    '#disabled' => \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_disabled'),
    '#default_value' => $email_attr,
    '#description' => 'This field is mandatory for login',
   // '#required' => 'true',
    '#attributes' => array(
		),
  );
  $form['miniorange_oauth_client_name_attr'] = array(
    '#type' => 'textfield',
    '#id' => 'text_field',
    '#title' => t('Name Attribute: '),
    '#description' => 'This field is mandatory for login',
    '#disabled' => \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_disabled'),
    //'#required' => 'true',
    '#default_value' => $name_attr,
    '#attributes' => array(
		),
  );


  $form['markup_cam'] = array(
    '#markup' => '<h3>Custom Attribute Mapping [PREMIUM]</h3><p>Add the Drupal field attributes in the Attribute Name textfield and add the OAuth Server attributes that you need to map with the drupal attributes in the OAuth Server Attribute Name textfield. Drupal Field Attributes will be of type text. Add the machine name of the attribute in the Drupal Attribute textfield.</p><p>For example: If the attribute name in the drupal is name then its machine name will be field_name.</p>',
  );


   $form['miniorange_oauth_attr5_name'] = array(
	'#type' => 'textfield',
  '#id' => 'text_field',
	'#title' => t('Attribute Name 1'),
	'#attributes' => array('placeholder' => 'Enter Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );

  $form['miniorange_oauth_server_attr5_name'] = array(
	'#type' => 'textfield',
  '#id' => 'text_field',
	'#title' => t('OAuth Server Attribute Name 1'),
	'#attributes' => array('placeholder' => 'Enter OAuth Server Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );

  $form['miniorange_oauth_attr2_name'] = array(
	'#type' => 'textfield',
  '#id' => 'text_field',
	'#title' => t('Attribute Name 2'),
	'#attributes' => array('placeholder' => 'Enter Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );

  $form['miniorange_oauth_server_attr2_name'] = array(
	'#type' => 'textfield',
  '#id' => 'text_field',
	'#title' => t('OAuth Server Attribute Name 2'),
	'#attributes' => array('placeholder' => 'Enter OAuth Server Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );

  $form['miniorange_oauth_attr3_name'] = array(
	'#type' => 'textfield',
  '#id' => 'text_field',
	'#title' => t('Attribute Name 3'),
	'#attributes' => array('placeholder' => 'Enter Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );

  $form['miniorange_oauth_attr3_name'] = array(
	'#type' => 'textfield',
  '#id' => 'text_field',
	'#title' => t('OAuth Server Attribute Name 3'),
	'#attributes' => array('placeholder' => 'Enter OAuth Server Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );

  $form['miniorange_oauth_attr4_name'] = array(
	'#type' => 'textfield',
  '#id' => 'text_field',
	'#title' => t('Attribute Name 4'),
	'#attributes' => array('placeholder' => 'Enter Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );

  $form['miniorange_oauth_server_attr4_name'] = array(
	'#type' => 'textfield',
  '#id' => 'text_field',
	'#title' => t('OAuth Server Attribute Name 4'),
	'#attributes' => array('placeholder' => 'Enter OAuth Server Attribute Name'),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );

   $form['markup_role'] = array(
    '#markup' => '<h3>Custom Role Mapping</h3>',
  );
  $form['miniorange_disable_attribute'] = array(
    '#type' => 'checkbox',
    '#title' => t('Do not update existing user&#39;s role <b>[PREMIUM]</b>'),
	'#disabled' => TRUE,
  );

   $form['miniorange_oauth_disable_role_update'] = array(
    '#type' => 'checkbox',
    '#title' => t('Check this option if you do not want to update user role if roles not mapped. <b>[PREMIUM]</b>'),
	  '#disabled' => TRUE,
  );

   $form['miniorange_oauth_disable_autocreate_users'] = array(
    '#type' => 'checkbox',
    '#title' => t('Check this option if you want to disable <b>auto creation</b> of users if user does not exist. <b>[PREMIUM]</b>'),
	'#disabled' => TRUE,
  );
/*
	$mrole= user_roles($membersonly = TRUE);
	//$drole = array_search(variable_get('miniorange_oauth_default_role',''),$mrole);

   $form['miniorange_oauth_default_mapping'] = array(
    '#type' => 'select',
    '#id' => 'miniorange_oauth_client_app',
	'#title' => t('Select default group for the new users'),
	'#options' => $mrole,
	//'#default_value' => $drole,
  '#disabled' => true,
   );

	foreach($mrole as $roles) {
    $rolelabel = str_replace(' ','',$roles);
    $form['miniorange_oauth_role_' . $rolelabel] = array(
	'#type' => 'textfield',
  '#id' => 'text_field',
	'#title' => t($roles),
	//'#default_value' => variable_get('miniorange_oauth_role_' . $rolelabel, ''),
	'#attributes' => array('placeholder' => 'Semi-colon(;) separated Group/Role value for ' . $roles),
	'#required' => FALSE,
	'#disabled' => TRUE,
  );*/


   $form['markup_role_signin'] = array(
    '#markup' => '<h3>Custom Login/Logout (Optional) [PREMIUM]</h3>'
  );

/*
	foreach($mrole as $drupalKey=>$drupalRoles) {

		$lbl = str_replace(' ','',$drupalRoles . '_sin');
		$l= str_replace(' ','',$drupalRoles . '_sout');
*/

		$form['miniorange_oauth_client_login_url'] = array(
			'#type' => 'textfield',
      '#id' => 'text_field',
			'#attributes' => array('placeholder' => 'Enter Login URL'),
			'#required' => FALSE,
			'#disabled' => TRUE,
		 );

		$form['miniorange_oauth_client_logout_url'] = array(
			'#type' => 'textfield',
      '#id' => 'text_field',
			'#attributes' => array('placeholder' => 'Enter Logout URL'),
			'#required' => FALSE,
			'#disabled' => TRUE,
		 );
    $form['miniorange_oauth_client_attr_setup_button'] = array(
    '#type' => 'submit',
    '#id' => 'button_config',
    '#value' => t('Save'),
    '#submit' => array('::miniorange_oauth_client_attr_setup_submit'),
  );
        return $form;

}
public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

}
function miniorange_oauth_client_attr_setup_submit($form, $form_state)
{
  $email_attr = $form['miniorange_oauth_client_email_attr']['#value'];
  $name_attr = $form['miniorange_oauth_client_name_attr']['#value'];
  $baseUrlvalue = $form['miniorange_oauth_client_base_url']['#value'];

  \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_base_url', $baseUrlvalue)->save();
  \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_email_attr_val', $email_attr)->save();
  \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_name_attr_val', $name_attr)->save();
  $app_values = \Drupal::config('miniorange_oauth_client.settings')->get('miniorange_oauth_client_appval');

  $app_values['miniorange_oauth_client_email_attr'] = $email_attr;
  $app_values['miniorange_oauth_client_name_attr'] = $name_attr;
  \Drupal::configFactory()->getEditable('miniorange_oauth_client.settings')->set('miniorange_oauth_client_appval',$app_values)->save();
 drupal_set_message(t('Attribute Mapping saved successfully.'));

    //drupal_set_message(t('Configurations saved successfully.'));
}
}