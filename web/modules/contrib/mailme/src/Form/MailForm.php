<?php
/**
 * @file
 * Contains \Drupal\mailme\Form\MailForm.
 */
namespace Drupal\mailme\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class MailForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailme_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	$form['container'] = array(
		'#type' => 'container',
		'#attributes' => array('class' => array('mail-form-container')),
	);
	$form['container']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name:'),
	  '#attributes' => array('class' => array('mail-form-input')),
      '#required' => TRUE,
    );
    $form['container']['email'] = array(
      '#type' => 'email',
      '#title' => t('Email:'),
	  '#attributes' => array('class' => array('mail-form-input')),
      '#required' => TRUE,
    );
	$form['container']['subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject:'),
	  '#attributes' => array('class' => array('mail-form-input')),
      '#required' => TRUE,
    );
	$form['container']['message'] = array(
      '#type' => 'textarea',
      '#title' => t('Message:'),
	  '#attributes' => array('class' => array('mail-form-input', 'mail-form-textarea')),
      '#required' => TRUE,
    );
    $form['container']['actions']['#type'] = 'actions';
    $form['container']['actions']['submit'] = array(
      '#type' => 'submit',
	  '#attributes' => array('class' => array('mail-form-submit')),
      '#value' => $this->t('Send'),
      '#button_type' => 'primary',
    );
    return $form;
  }
  /**
   * {@inheritdoc}
   */
   
  // Takes information from form and sends an email using Drupal's email plugin.
  public function submitForm(array &$form, FormStateInterface $form_state) {
	
	$config = $this->config('mailme.settings');
	$to = $config->get('mailme_email');
	
	$msg = 
	"<p>Name: " . $form_state->getValue('name') . "</p>" . "<p>Email: " . $form_state->getValue('email') . "</p>" . "<p>Message: " . $form_state->getValue('message') . "</p>";
	
	$siteName = \Drupal::config('system.site')->get('name');	
	$mailManager = \Drupal::service('plugin.manager.mail');
	$langcode = \Drupal::currentUser()->getPreferredLangcode();
	$params['context']['subject'] = "MailMe - New Message on " . $siteName . ": " . $form_state->getValue('subject');
	$params['context']['message'] = $msg;

	$result = $mailManager->mail('system', 'mail', $to, $langcode, $params);
	
	// Check results of sending
	if ($result['result'] != true) {
		$message = t('There was a problem sending your email notification to @email.', array('@email' => $to));
		drupal_set_message($message, 'error');
		\Drupal::logger('mail-log')->error($message);
		return;
	}
	//Sent OK
	$message = t('An email notification has been sent to @email ', array('@email' => $to));
	drupal_set_message($message);
	\Drupal::logger('mail-log')->notice($message);
  }
}







