<?php

namespace Drupal\get_a_quote\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;

/**
 * Provides the GetQuote form.
 */
class GetQuote extends FormBase {

  /**  
   * {@inheritdoc}  
   */  
  public function getFormId() {  
    return 'get_quote_form';  
  }  
  
  /**  
   * {@inheritdoc}  
   */  
  public function buildForm(array $form, FormStateInterface $form_state) {  
  
    $form['quotefieldset'] = [
	  '#type' => 'fieldset',
	];
	
	$form['quotefieldset']['name'] = [  
      '#type' => 'textfield',  
      '#title' => $this->t('Your Name'),
	  '#required' => TRUE,
    ];

    $form['quotefieldset']['email'] = [  
      '#type' => 'email',  
      '#title' => $this->t('Email'),  
      '#required' => TRUE,	  
    ];
	
	$form['quotefieldset']['number'] = [
      '#type' => 'tel',
      '#title' => t('Mobile no'),
      '#required' => TRUE,
    ];
	
	$form['quotefieldset']['quote_subject'] = [  
      '#type' => 'textfield',  
      '#title' => $this->t('Subject'),
      '#required' => TRUE,
    ];
	
	$form['quotefieldset']['quote_description'] = [  
      '#type' => 'textarea',  
      '#title' => $this->t('Description'),
      '#required' => TRUE,
    ];
	
	$form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Quote'),
      '#button_type' => 'primary',
    ];

    return $form;  
  }  
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('number')) < 10) {
      $form_state->setErrorByName('number', $this->t('Mobile number is too short.'));
    }
  }
  
  /**  
   * {@inheritdoc}  
   */  
  public function submitForm(array &$form, FormStateInterface $form_state) {   
  
	// sending mail
	$mailManager = \Drupal::service('plugin.manager.mail');
	$module = 'get_a_quote';
	$key = 'send_quote';
	$to = $form_state->getValue('email');
  $message = '<p>Hi ' . ucfirst($form_state->getValue('name')). '</p>';
  $message .= '<p>' . t('Your have recently requested a Quote for the product in your cart.') . '</p>';
  $message .= '<p>' . t('Summary : ') . '</p>';
  $message .= '<p>' . t('Your Name: @yourname', ['@yourname' => $form_state->getValue('name')]) . '</p>';
  $message .= '<p>' . t('Email: @email', ['@email' => $form_state->getValue('email')]) . '</p>';
  $message .= '<p>' . t('Contact Number: @contnumber', ['@contnumber' => $form_state->getValue('number')]) . '</p>';
  $message .= '<p>' . t('Message: @message', ['@message' => $form_state->getValue('quote_description')]) . '</p>';
  $message .= '<p>' . t('Thank you for your Query, We will contact you soon') . '</p>'; 

	$params['message'] = MailFormatHelper::htmlToText($message);
	$params['subject'] = $form_state->getValue('quote_subject') .' | ' . \Drupal::config('system.site')->get('name');
	$langcode = \Drupal::currentUser()->getPreferredLangcode();
  $from = \Drupal::config('system.site')->get('mail');
	$send = true;
	$result = $mailManager->mail($module, $key, $to, $langcode, $params, $from, $send);
	if ($result['result'] !== true) {
    \Drupal::messenger()
      ->addError($this->t('There was a problem sending your message and it was not sent.'));
	}
	else {
    \Drupal::messenger()
      ->addStatus($this->t('Your message has been sent.'));
	}
  
  global $base_url;
  $key2 = 'receive_quote';
  $to2 = \Drupal::config('system.site')->get('mail');
  $message2 = '<p>Hi ' . \Drupal::config('system.site')->get('mail') . '</p>';
  $message2 .= '<p>' . t('A new Quote Request has been received from '.$form_state->getValue('email')) . '</p>';
  $message2 .= '<p>' . t('Requester Summary : ') . '</p>';
  $message2 .= '<p>' . t('Name: @yourname', ['@yourname' => $form_state->getValue('name')]) . '</p>';
  $message2 .= '<p>' . t('Email: @email', ['@email' => $form_state->getValue('email')]) . '</p>';
  $message2 .= '<p>' . t('Contact Number: @contnumber', ['@contnumber' => $form_state->getValue('number')]) . '</p>';
  $message2 .= '<p>' . t('Subject: @subject', ['@subject' => $form_state->getValue('quote_subject')]) . '</p>';
  $message2 .= '<p>' . t('Message: @message', ['@message' => $form_state->getValue('quote_description')]) . '</p>';
  $message2 .= '<p>' . t('For product information, visit '.$base_url.'/admin/commerce/orders/carts') . '</p>'; 

	$params['message2'] = MailFormatHelper::htmlToText($message2);
  $result2 = $mailManager->mail($module, $key2, $to2, $langcode, $params, $from, $send);  

  }  
}