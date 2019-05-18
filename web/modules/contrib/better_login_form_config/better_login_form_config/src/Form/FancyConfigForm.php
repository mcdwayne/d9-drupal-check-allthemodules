<?php
/**
 
 * @file
 
 * Contains \Drupal\better_login_form_config\Form\FancyConfigForm.
 
 */
 
namespace Drupal\better_login_form_config\Form;
 
use Drupal\Core\Form\ConfigFormBase;
 
use Drupal\Core\Form\FormStateInterface;
 
class FancyConfigForm extends ConfigFormBase {
 
  /**
 
   * {@inheritdoc}
 
   */
 
  public function getFormId() {
 
    return 'fancy_login_config_form';
 
  }
 
  /**
 
   * {@inheritdoc}
 
   */
 
  public function buildForm(array $form, FormStateInterface $form_state) {
 
    $form = parent::buildForm($form, $form_state);
    $login_form_config = $this->config('fancy_login.settings');
	$form['login_form_setting'] = [
      '#type' => 'details',
      '#title' => $this->t('Login Form'),
      '#open' => TRUE,
    ];
	
	$form['login_form_setting']['form_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Login Form Title'),
    '#size' => 60,
    '#maxlength' => USERNAME_MAX_LENGTH,
    '#attributes' => array('class' => array('form-title')),
    '#required' => TRUE,
    '#default_value' => !empty($login_form_config->get('form_title')) ? $login_form_config->get('form_title') : 'User account',
  );
  $form['login_form_setting']['username_label'] = array(
    '#type' => 'textfield',
    '#title' => t('Username Label'),
    '#size' => 60,
    '#maxlength' => USERNAME_MAX_LENGTH,
    '#attributes' => array('class' => array('username-label')),
    '#required' => TRUE,
   '#default_value' => !empty($login_form_config->get('username_label')) ? $login_form_config->get('username_label') : 'Username',
  );
  $form['login_form_setting']['username_description'] = array(
    '#type' => 'textfield',
    '#title' => t('Username Description'),
    '#size' => 60,
    '#maxlength' => USERNAME_MAX_LENGTH,
    '#attributes' => array('class' => array('username-description')),
    '#default_value' => !empty($login_form_config->get('username_description')) ? $login_form_config->get('username_description') : '',
  );
  $form['login_form_setting']['password_label'] = array(
    '#type' => 'textfield',
    '#title' => t('Password Label'),
    '#size' => 60,
    '#maxlength' => USERNAME_MAX_LENGTH,
    '#attributes' => array('class' => array('password-label')),
    '#required' => TRUE,
    '#default_value' => !empty($login_form_config->get('password_label')) ? $login_form_config->get('password_label') : 'Password',
  );
  $form['login_form_setting']['password_description'] = array(
    '#type' => 'textfield',
    '#title' => t('Password Description'),
    '#size' => 60,
    '#maxlength' => USERNAME_MAX_LENGTH,
    '#attributes' => array('class' => array('password-description')),
    '#default_value' => !empty($login_form_config->get('password_description')) ? $login_form_config->get('password_description') : 'Enter the password that accompanies your username.',
  );

  $form['login_form_setting']['login_button'] = array(
    '#type' => 'textfield',
    '#title' => t('Login button text'),
    '#size' => 60,
    '#maxlength' => USERNAME_MAX_LENGTH,
    '#attributes' => array('class' => array('login-button')),
	
    '#default_value' => !empty($login_form_config->get('login_button')) ? $login_form_config->get('login_button'): 'Log in',
  );
 $form['login_form_setting']['include_login'] = array(
    '#type' => 'checkbox',
   '#default_value' => !empty($login_form_config->get('include_login')) ? $login_form_config->get('include_login') : 0,
    '#title' => t('Exclude login Form Template'),
  );
  
  $form['forgot_form_setting'] = [
      '#type' => 'details',
      '#title' => $this->t('Forgot Password Form'),
      '#open' => false,
    ];
	
 $form['forgot_form_setting']['forgot_form_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Forgot Form Title '),
    '#size' => 60,
    '#attributes' => array('class' => array('forgot_form_title')),
	'#required' => TRUE,
    '#default_value' => !empty($login_form_config->get('forgot_form_title')) ? $login_form_config->get('forgot_form_title'): 'Forgot your password? ',
  );
 $form['forgot_form_setting']['forgot_form_username'] = array(
    '#type' => 'textfield',
    '#title' => t('User Name or Email  Label '),
    '#size' => 60,
    '#attributes' => array('class' => array('forgot_form_username')),
	'#required' => TRUE,
    '#default_value' => !empty($login_form_config->get('forgot_form_username')) ? $login_form_config->get('forgot_form_username'): 'Username or email address. ',
  );	
 $form['forgot_form_setting']['forgot_form_username_desc'] = array(
    '#type' => 'textfield',
    '#title' => t('User Name or Email  Description '),
    '#size' => 60,
    '#attributes' => array('class' => array('forgot_form_username_desc')),
    '#default_value' => !empty($login_form_config->get('forgot_form_username_desc')) ? $login_form_config->get('forgot_form_username_desc'): 'Password reset instructions will be sent to your registered email address.',
  );

  $form['forgot_form_setting']['include_forgot_template'] = array(
    '#type' => 'checkbox',
   '#default_value' => !empty($login_form_config->get('include_forgot_template')) ? $login_form_config->get('include_forgot_template') : 0,
    '#title' => t('Exclude Forgot Password Form Template'),
  );  
 $form['register_form_setting'] = [
      '#type' => 'details',
      '#title' => $this->t('Register Form'),
      '#open' => false,
    ];
	
 $form['register_form_setting']['register_form_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Register Form Title '),
    '#size' => 60,
	'#required' => TRUE,
    '#attributes' => array('class' => array('register_form_title')),
    '#default_value' => !empty($login_form_config->get('register_form_title')) ? $login_form_config->get('register_form_title'): 'Register',
  );

$form['register_form_setting']['register_form_mail'] = array(
    '#type' => 'textfield',
    '#title' => t('Register Mail  Label '),
    '#size' => 60,
	'#required' => TRUE,
    '#attributes' => array('class' => array('register_form_mail')),
    '#default_value' => !empty($login_form_config->get('register_form_mail')) ? $login_form_config->get('register_form_mail'): 'Email address',
  );
$form['register_form_setting']['register_mail_desc'] = array(
    '#type' => 'textfield',
    '#title' => t(' EMail  Description '),
    '#size' => 90,
	'#maxlength' => 255,
    '#attributes' => array('class' => array('register_mail_desc')),
    '#default_value' => !empty($login_form_config->get('register_mail_desc')) ? $login_form_config->get('register_mail_desc'): 'A valid email address. All emails from the system will be sent to this address. The email address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by email.',
  );	  

$form['register_form_setting']['register_form_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Register Name Label '),
    '#size' => 60,
	'#required' => TRUE,
    '#attributes' => array('class' => array('register_form_name')),
    '#default_value' => !empty($login_form_config->get('register_form_name')) ? $login_form_config->get('register_form_title'): 'Username',
  );
$form['register_form_setting']['register_form_name_desc'] = array(
    '#type' => 'textfield',
    '#title' => t('User Name Description '),
    '#size' => 90,
	'#maxlength' => 255,
    '#attributes' => array('class' => array('register_form_name_desc')),
    '#default_value' => !empty($login_form_config->get('register_form_name_desc')) ? $login_form_config->get('register_form_name_desc'): "Several special characters are allowed, including space, period (.), hyphen (-), apostrophe ('), underscore (_), and the @ sign.",
  );
  
  $form['register_form_setting']['register_form_button'] = array(
    '#type' => 'textfield',
    '#title' => t('Register button Text'),
    '#size' => 60,
    '#attributes' => array('class' => array('register_form_button')),
    '#default_value' => !empty($login_form_config->get('register_form_button')) ? $login_form_config->get('register_form_button'): "Create new account",
  );
 $form['register_form_setting']['include_regi_template'] = array(
    '#type' => 'checkbox',
   '#default_value' => !empty($login_form_config->get('include_regi_template')) ? $login_form_config->get('include_regi_template') : 0,
    '#title' => t('Exclude Register Form Template'),
  );
  

  $form['create_account'] = array(
    '#type' => 'checkbox',
    '#default_value' => !empty($login_form_config->get('create_account')) ? $login_form_config->get('create_account') : 0,
    '#title' => t('Create New Account'),
  );
  $form['forgot_password'] = array(
    '#type' => 'checkbox',
   '#default_value' => !empty($login_form_config->get('forgot_password')) ? $login_form_config->get('forgot_password') : 0,
    '#title' => t('Forgot your password?'),
  );
  /*$form['login_block'] = array(
    '#type' => 'checkbox',
    '#default_value' => !empty($login_form_config->get('login_block')) ? $login_form_config->get('login_block') : 0,
    '#title' => t('Configure the login block as well?'),
  );
*/
 
    return $form;
 
  }
 
  /**
 
   * {@inheritdoc}
 
   */
 
  public function submitForm(array &$form, FormStateInterface $form_state) {
		$config = $this->config('fancy_login.settings');
		$config->set('form_title', $form_state->getValue('form_title'));
		$config->set('username_label', $form_state->getValue('username_label'));
		$config->set('username_description', $form_state->getValue('username_description'));
		$config->set('password_label', $form_state->getValue('password_label'));
		$config->set('password_description', $form_state->getValue('password_description'));
		$config->set('login_button', $form_state->getValue('login_button'));
		$config->set('create_account', $form_state->getValue('create_account'));
		$config->set('forgot_password', $form_state->getValue('forgot_password'));
		//$config->set('login_block', $form_state->getValue('login_block'));
		$config->set('forgot_form_title', $form_state->getValue('forgot_form_title'));
		$config->set('register_form_title', $form_state->getValue('register_form_title'));
		$config->set('register_form_mail', $form_state->getValue('register_form_mail'));
		$config->set('register_mail_desc', $form_state->getValue('register_mail_desc'));
		$config->set('register_form_name', $form_state->getValue('register_form_name'));
		$config->set('register_form_name_desc', $form_state->getValue('register_form_name_desc'));
		
		$config->set('forgot_form_username_desc', $form_state->getValue('forgot_form_username_desc'));
		$config->set('forgot_form_username', $form_state->getValue('forgot_form_username'));
		$config->set('forgot_form_button', $form_state->getValue('forgot_form_button'));
		
		$config->set('include_login', $form_state->getValue('include_login'));
		$config->set('include_regi_template', $form_state->getValue('include_regi_template'));
		$config->set('include_forgot_template', $form_state->getValue('include_forgot_template'));


		
    $config->save();
    return parent::submitForm($form, $form_state);
	
	drupal_flush_all_caches();
 
  }
 
  /**
 
   * {@inheritdoc}
 
   */
 
  protected function getEditableConfigNames() {
 
    return [
 
      'fancy_login.settings',
 
    ];
 
  }
}