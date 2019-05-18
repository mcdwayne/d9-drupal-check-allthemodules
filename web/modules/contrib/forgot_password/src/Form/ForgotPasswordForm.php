<?php
namespace Drupal\forgot_password\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\forgot_password\Manager\StepManager;
use Drupal\forgot_password\Step\StepsEnum;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Provides multi step Forgot Password form.
 *
 * @package Drupal\forgot_password\Form
 */
class ForgotPasswordForm extends FormBase {
  use StringTranslationTrait;

  /**
   * Step Id.
   *
   * @var \Drupal\forgot_password\Step\StepsEnum
   */
  protected $stepId;

  /**
   * Multi steps of the form.
   *
   * @var \Drupal\forgot_password\Step\StepInterface
   */
  protected $step;

  /**
   * Step manager instance.
   *
   * @var \Drupal\forgot_password\Manager\StepManager
   */
  protected $stepManager;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->stepId = StepsEnum::STEP_ONE;
    $this->stepManager = new StepManager();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'forgotpassword_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['wrapper-messages'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'messages-wrapper',
      ],
    ];

    $form['wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'form-wrapper',
      ],
    ];

    // Get step from step manager.
    $this->step = $this->stepManager->getStep($this->stepId);

    // Attach step form elements.
    $form['wrapper'] += $this->step->buildStepFormElements();

    // Attach buttons.
    $form['wrapper']['actions']['#type'] = 'actions';
    $buttons = $this->step->getButtons();
    foreach ($buttons as $button) {
		/** @var \Drupal\skipta_user\Button\ButtonInterface $button */
		$form['wrapper']['actions'][$button->getKey()] = $button->build();

		if ($button->ajaxify()) {
			// Add ajax to button.
			$form['wrapper']['actions'][$button->getKey()]['#ajax'] = [
				'callback' => [$this, 'loadStep'],
				'wrapper' => 'form-wrapper',
				'effect' => 'fade',
			];
		}

      $callable = [$this, $button->getSubmitHandler()];
      if ($button->getSubmitHandler() && is_callable($callable)) {
        // Attach submit handler to button, so we can execute it later on..
        $form['wrapper']['actions'][$button->getKey()]['#submit_handler'] = $button->getSubmitHandler();
      }
    }

    return $form;

  }

  /**
   * Ajax callback to load new step.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function loadStep(array &$form, FormStateInterface $form_state) {
	$response = new AjaxResponse();

    $messages = drupal_get_messages();
    if (!empty($messages)) {
      // Form did not validate, get messages and render them.
      $messages = [
        '#theme' => 'status_messages',
        '#message_list' => $messages,
        '#status_headings' => [
          'status' => $this->t('Status message'),
          'error' => $this->t('Error message'),
          'warning' => $this->t('Warning message'),
        ],
      ];
      $response->addCommand(new HtmlCommand('#messages-wrapper', $messages));
    }
    else {
      // Remove messages.
      $response->addCommand(new HtmlCommand('#messages-wrapper', ''));
    }

    // Update Form.
    $response->addCommand(new HtmlCommand('#form-wrapper', $form['wrapper']));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // Only validate if validation doesn't have to be skipped.
    // For example on "previous" button.
    if (empty($triggering_element['#skip_validation']) && $fields_validators = $this->step->getFieldsValidators()) {
      $nextstep = $triggering_element['#goto_step'];
      // Validate fields.
      foreach ($fields_validators as $field => $validators) {
        // Validate all validators for field.
        $value = $form_state->getValue($field);
        
        //Step 1 validation check
        if($field == 'user_email') {
          if($value <> NULL) {
            $users_detail = \Drupal::entityTypeManager()->getStorage('user')
            ->loadByProperties(['mail' => $value]);
            $user_data = reset($users_detail);

            if($user_data <> NULL) {
              $result = $value;
              $_SESSION['skipta_user']['useremail'] = $value;
            }
            else {
              $result = $form_state->setErrorByName($field, 'Invalid Email Address');
            }
            //return is_array($result) ? !empty(array_filter($result)) : !empty($result);
          }
          else {
            $result = $form_state->setErrorByName($field, 'Required Email Address');
            //return is_array($result) ? !empty(array_filter($result)) : !empty($result);
          }
        }
        
        //Step 2 validation check
        /*if(($field === 'user_securityquestion') && ($value <> NULL)) {
          $useremail = $_SESSION['skipta_user']['useremail'];
          if($useremail <> NULL) {
            $userdata = get_skipta_userdetails($useremail);
            $user_id = $userdata['uid'];
            $user_savedAnswer = $userdata['security_answer'];
            
            if(strtolower($user_savedAnswer) == strtolower($value) ) {    
              $result = $value;
            } 
            else {
              $result = $form_state->setErrorByName($field, 'Incorrect Security Answer');
            }
          }
          else {
            $result = $form_state->setErrorByName($field, 'Incorrect Security Answer');
          }
          //return is_array($result) ? !empty(array_filter($result)) : !empty($result);
        }
        else*/
				if(($field === 'everification_code') && ($value <> NULL)) {
          $useremail = $_SESSION['forgot_password']['useremail'];
          if($useremail <> NULL) {
            $userdata = get_user_details($useremail);
            $user_id = $userdata['uid'];
            $otp = \Drupal::database()->select('user_forgotpass_otp', 'otp')
            ->fields('otp', ['code'])
            ->condition('user_id', $user_id)
            ->condition('mode', 2)
            ->condition('used', 0, '=')
            ->execute()
            ->fetchField();
            if(($otp <> NULL) && ($otp == $value)) {
              $result = $value;
            } 
            else {
              $result = $form_state->setErrorByName($field, 'Invalid OTP');
            }
          } 
          else {
            $result = $form_state->setErrorByName($field, 'Invalid OTP');
          }
        } 
        /*elseif(($field === 'mverification_code') && ($value <> NULL)) {
          $useremail = $_SESSION['skipta_user']['useremail'];
          if($useremail <> NULL) {
            $userdata = get_skipta_userdetails($useremail);
            $user_id = $userdata['uid'];
            $otp = \Drupal::database()->select('skipta_user_mobile_otp', 'otp')
            ->fields('otp', ['code'])
            ->condition('user_id', $user_id)
            ->condition('mode', 1)
            ->condition('used', 0, '=')
            ->execute()
            ->fetchField();
            if(($otp <> NULL) && ($otp == $value)) {
              $result = $value;
            }
            else {
              $result = $form_state->setErrorByName($field, 'Invalid/Expire Mobile OTP');
            }
          } 
          else {
            $result = $form_state->setErrorByName($field, 'Invalid/Expire Mobile OTP');
          }
          //return is_array($result) ? !empty(array_filter($result)) : !empty($result);
        }
        else {
          $user_securityquestion = $form_state->getValue('user_securityquestion');
          $everification_code = $form_state->getValue('everification_code');
          $mverification_code = $form_state->getValue('mverification_code');
          if($nextstep == 3) {
            if(empty($user_securityquestion) && empty($everification_code) && empty($mverification_code)) {
              $result = $form_state->setErrorByName($mverification_code, 'Please select an option for reset password to proceed further');
              //return is_array($result) ? !empty(array_filter($result)) : !empty($result);
            }
          }
        }*/
        
        // Step 3 validation
        if($field == 'pass_fields') {
          $user_pass = $form_state->getValue('pass_fields');
          if ($user_pass === "") {
            $result = $form_state->setErrorByName('pass_fields', 'Please enter password.');
          } else {
            $email = strtolower($_SESSION['forgot_password']['useremail']);
            $userdata = get_user_details($email);
            $first_name = strtolower($userdata['fname']);
            $last_name = strtolower($userdata['lname']);
            $userpass_lower = strtolower($user_pass);
            
            // check for password should not contain firstname
            if ($first_name != "" && strpos($userpass_lower, $first_name) !== false) {
              $result = $form_state->setErrorByName('pass_fields', 'Password should not contain your first name!');
            }
            
            // check for password should not contain lastname
            if ($last_name != "" && strpos($userpass_lower, $last_name) !== false) {
              $result = $form_state->setErrorByName('pass_fields', 'Password should not contain your last name!');
            }
            
            // check for password should not contain email
            if ($email != "" && strpos($userpass_lower, $email) !== false) {
              $result = $form_state->setErrorByName('pass_fields', 'Password should not contain your email address!');
            }
            $correct_pass = skipta_user_password_strength($user_pass);
            if ($correct_pass !== "") {
              $form_state->setErrorByName('pass_fields', $correct_pass);
            }
          }
        }
      
        if($field == 'agree') {
          $agree = $form_state->getValue('agree');
          if ($agree == 0) {
            $result = $form_state->setErrorByName('agree', 'You must agree first');
          }
        }
      }
    }  
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save filled values to step. So we can use them as default_value later on.
    $values = [];
    foreach ($this->step->getFieldNames() as $name) {
      $values[$name] = $form_state->getValue($name);
    }
    $this->step->setValues($values);
    // Add step to manager.
    $this->stepManager->addStep($this->step);
    // Set step to navigate to.
    $triggering_element = $form_state->getTriggeringElement();
    $this->stepId = $triggering_element['#goto_step'];

    // If an extra submit handler is set, execute it.
    // We already tested if it is callable before.
    if (isset($triggering_element['#submit_handler'])) {
      $this->{$triggering_element['#submit_handler']}($form, $form_state);
    }

    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler for last step of form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface.
   */
	public function submitValues(array &$form, FormStateInterface $form_state) {
		// Submit all values to DB or do whatever you want on submit.
		$newpassword = $form_state->getValue('pass_fields');
		$uid = $form_state->getValue('user_id');
		$account = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
		$account->setPassword($newpassword);
		$account->save();
		$objMarketo = new MarketoAPIController();
		$config = \Drupal::config('skipta_core.marketo_templete_variables');
		$programName = $config->get('programName');
		$campaignName = $config->get('password_reset_confirmation_campaignName');
		$objMarketo->programName = $programName;
		$objMarketo->campaignName = $campaignName;
		$objMarketo->email = $_SESSION['skipta_user']['useremail'];
		$objMarketo->customToken = "";
		$res = $objMarketo->sendEmail();
	}

	/**
		* Callback for forgot password - Mobile Verification
	  *
	*/
	public function resendOTPAjaxCallback(array &$form, FormStateInterface $form_state) {
		$useremail = $_SESSION['skipta_user']['useremail'];
		if(!empty($useremail)) {
			$userdata = get_skipta_userdetails($useremail);
			//Send OTP on mobile
			$otp = mt_rand(1000, 9999);
			$mobile = $userdata['mobile_no'];
			$output = \Drupal\skipta_user\Controller\ResetPasswordFormController::skipta_forgotpassword_otp($mobile, $otp);
      
      //Chck that unused otp exist or not
      $query = \Drupal::database()->select('skipta_user_mobile_otp', 'sumo');
      $query->fields('sumo', ['id']);
      $query->condition('sumo.user_id', $userdata['uid']);
      $query->condition('sumo.mode', 1);
      $query->condition('sumo.used', 0);
      $results = $query->execute();
      $row = $results->fetchAssoc();
      if(empty($row)){
        //Insert OTP record to table.
        \Drupal::database()->insert('skipta_user_mobile_otp')
        ->fields([
          'user_id',
          'code',
          'mode',
          'created',
          'used',
        ])
        ->values(array(
          $userdata['uid'],
          $otp,
          '1',
          time(),
          '0',
        ))
        ->execute();
      }
      else {
        //Update OTP record to table.
        $query_update = \Drupal::database()->update('skipta_user_mobile_otp')
        ->fields([
          'code' => $otp,
          'created' => time(),
        ])
        ->condition('user_id', $userdata['uid'])
        ->condition('mode', 1)
        ->condition('used', 0)
        ->execute();
      }
		}
    return $form;
	}

	/**
		* Callback for forgot password - Email Verification
	  *
	*/
	public function resendEmailOTPAjaxCallback(array &$form, FormStateInterface $form_state) {
		$everification_type = $form_state->getValue('everification_email');
		$useremail = $_SESSION['skipta_user']['useremail'];
		if(!empty($useremail)) {
			$userdata = get_skipta_userdetails($useremail);
			//Send OTP on email
			$everification_email = '';
			if($everification_type == 'pemail') {
				$everification_email = $useremail;
			}
      elseif($everification_type == 'semail') {
				$everification_email = $userdata['secondary_email'];
			}
      else {
				//Nothing to do
			}
			$otp = mt_rand(100000, 999999);
			$output = \Drupal\skipta_user\Controller\ResetPasswordFormController::skipta_forgotpassword_emailotp($everification_email, $otp );
      
      //Chck that unused otp exist or not
      $query = \Drupal::database()->select('skipta_user_mobile_otp', 'sumo');
      $query->fields('sumo', ['id']);
      $query->condition('sumo.user_id', $userdata['uid']);
      $query->condition('sumo.mode', 2);
      $query->condition('sumo.used', 0);
      $results = $query->execute();
      $row = $results->fetchAssoc();
      if(empty($row)) {
        //Insert OTP record to table.
        \Drupal::database()->insert('skipta_user_mobile_otp')
        ->fields([
          'user_id',
          'code',
          'mode',
          'created',
          'used',
        ])
        ->values(array(
          $userdata['uid'],
          $otp,
          '2',
          time(),
          '0',
        ))
        ->execute();
      }
      else {
        //Update OTP record to table.
        $query_update = \Drupal::database()->update('skipta_user_mobile_otp')
        ->fields([
          'code' => $otp,
          'created' => time(),
        ])
        ->condition('user_id', $userdata['uid'])
        ->condition('mode', 2)
        ->condition('used', 0)
        ->execute();
      }
		}
    return $form;
	}
}