<?php

namespace Drupal\corporatelogin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class HelloWorldCustomForm.
 */
class CorporateLoginForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'corporatelogin_custom_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = array(
      '#type' => 'email',
	  '#description' => t('Please enter your corporate email address to login'),
      '#maxlength' => 255,
      '#size' => 64,
      '#title' => $this->t('email'),
      '#weight' => '0',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Corporate Login'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // parent::validateForm($form, $form_state);
		$form_state_values = $form_state->getValues();
		$corpEmailID = $form_state_values['email'];
		$corpEmailID_split = explode('@', $corpEmailID);
		
		$corpUserName = $corpEmailID_split[0];
		$corpUserDomain = $corpEmailID_split[1];
		$hasCorporateDomain = db_select('corporate_login_details', 'n')
		->fields('n')
		->condition('email', '%' . db_like($corpUserDomain) . '%', 'LIKE')
		->execute()
		->fetchAssoc();	
		// print_r($corpEmailID_split);
		
		if ($hasCorporateDomain) {
			return TRUE;
		}
		else{
			$form_state->setErrorByName('email', t('Sorry, you are not a Corporate Account user!'));			
		}
		
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
		$form_state_values = $form_state->getValues();
		$corpEmailID = $form_state_values['email'];
		$corpEmailID_split = explode('@', $corpEmailID);
		
		$corpUserName = $corpEmailID_split[0];
		// $corpUserDomain = $corpEmailID_split[1];
		// $hasCorporateDomain = db_select('corporate_login_details', 'n')
		// ->fields('n')
		// ->condition('email', '%' . db_like($corpUserDomain) . '%', 'LIKE')
		// ->execute()
		// ->fetchAssoc();	
		// print_r($corpEmailID_split);
		
		// if ($hasCorporateDomain) {			
			
			
			$db = \Drupal::database();
			$checkExistsQuery = $db->select('users_field_data', 'ufd');
			$checkExistsQuery->fields('ufd');
			$checkExistsQuery->condition('mail', $corpEmailID, "=");
			$checkExistsQueryResult = $checkExistsQuery->execute()->fetchAll();
			// print_r($checkExistsQueryResult);
			// exit;
			if (!$checkExistsQueryResult) {
				// print_r($checkExistsUsers);
				echo "Not Exist";
				// exit;
			    $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
				$user = \Drupal\user\Entity\User::create();
				// The Basics
				$user->setUsername(''.$corpUserName.'');  // You could also just set this to "Bob" or something...
				$user->setPassword(user_password());
				$user->setEmail(''.$corpEmailID.'');
				$user->enforceIsNew();  // Set this to FALSE if you want to edit (resave) an existing user object			 
				// Optional settings  <-- Thanks to http://drupal8.ovh/ for these suggestions!
				$user->set("init", ''.$corpEmailID.'');
				$user->set("langcode", $lang);
				$user->set("preferred_langcode", $lang);
				$user->set("preferred_admin_langcode", $lang);
				$user->activate();			 
				// Save user
				$user->save();
				// Login automatically
				user_login_finalize($user);			
			}
			else {
				// Login automatically if user email already exist		
				$userLoadEmail = user_load_by_mail($corpEmailID);
				$userID = $userLoadEmail->id();
				$account = \Drupal\user\Entity\User::load($userID);
				\Drupal::service('session')->migrate();
				\Drupal::service('session')->set('uid', $account->id());
				\Drupal::moduleHandler()->invokeAll('user_login', array($account));
			}
		// }
		// else {
			// echo "you are not a corporate";
			// exit;
			// $form_state->setErrorByName('email', t('Error Message'));
			// drupal_set_message(t('Sorry, you are not a Corporate Account user!'), 'error');
			// $form_state->setRebuild();
		// }
		// exit;

  }

}
