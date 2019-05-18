<?php
/**
 * @file
 * Contains \Drupal\resume\Form\ResumeForm.
 */
namespace Drupal\examplelist\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\examplelist\Controller\ResumeStorage;
use Symfony\Component\HttpFoundation\Request;

class ResumeeditForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'resume_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	 $parameters = \Drupal::service('current_route_match');
	  $id  = $parameters->getParameter('id');
	//print "<pre>"; print_r($parameters);die;
	$records = ResumeStorage::get($id);
	//foreach ($records as $id => $content) {}
	
	//print_r($records);die;
    $form['candidate_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Candidate Name:'),
      '#required' => TRUE,
	  '#default_value'=>$records->candidate_name
    );

    $form['candidate_mail'] = array(
      '#type' => 'email',
      '#title' => t('Email ID:'),
      '#required' => TRUE,
	  '#default_value'=>$records->candidate_mail
    );

    $form['candidate_number'] = array (
      '#type' => 'tel',
      '#title' => t('Mobile no'),
	  '#default_value'=>$records->candidate_number
    );

    $form['candidate_dob'] = array (
      '#type' => 'date',
      '#title' => t('DOB'),
      '#required' => TRUE,
	  '#default_value'=>$records->candidate_dob
    );

    $form['candidate_gender'] = array (
      '#type' => 'select',
      '#title' => ('Gender'),
      '#options' => array(
        'Female' => t('Female'),
        'male' => t('Male'),
      ),
	  '#default_value'=>$records->candidate_gender
    );

    $form['candidate_confirmation'] = array (
      '#type' => 'radios',
      '#title' => ('Are you above 18 years old?'),
      '#options' => array(
        'Yes' =>t('Yes'),
        'No' =>t('No')
      ),
	  '#default_value'=>$records->candidate_confirmation
    );

    $form['candidate_copy'] = array(
      '#type' => 'checkbox',
      '#title' => t('Send me a copy of the application.'),
	  '#default_value'=>$records->candidate_copy
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
    public function validateForm(array &$form, FormStateInterface $form_state) {

      if (strlen($form_state->getValue('candidate_number')) < 10) {
        $form_state->setErrorByName('candidate_number', $this->t('Mobile number is too short.'));
      }

    }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

   // drupal_set_message($this->t('@can_name ,Your application is being submitted!', array('@can_name' => $form_state->getValue('candidate_name'))));

    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }
	$parameters = \Drupal::service('current_route_match');
	$id  = $parameters->getParameter('id');
	
	 $update = db_update('resume')
    ->fields(array(
      'candidate_name' => $form_state->getValue('candidate_name'), 
      'candidate_mail' => $form_state->getValue('candidate_mail'),
      'candidate_number' => $form_state->getValue('candidate_number'),
      'candidate_dob' => $form_state->getValue('candidate_dob'),   
      'candidate_gender' => $form_state->getValue('candidate_gender'),
      'candidate_confirmation' => $form_state->getValue('candidate_confirmation'),	
      'candidate_copy' => $form_state->getValue('candidate_copy')	  
    ))->condition('id',$id)->execute();
	//print_r( $update);die;
    drupal_set_message("successfully updated Security Settings"); 
	//$form_state['redirect'] = 'resumelist';
	$url = Url::fromRoute('resumelist.content');
	$form_state->setRedirectUrl($url);
   }
}