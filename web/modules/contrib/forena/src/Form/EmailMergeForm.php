<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 1/17/2018
 * Time: 11:05 AM
 */

namespace Drupal\forena\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\forena\DocManager;
use Drupal\forena\FrxPlugin\Document\EmailMerge;

class EmailMergeForm extends FormBase {

  public function getFormId() {
    return 'forena_email_merge_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    /** @var EmailMerge $d */
    $d = DocManager::instance()->getDocument();
    $input_format = \Drupal::config('forena.settings')->get('email_input_format');
    $email_override = \Drupal::config('forena.settings')->get('email_override');

    $values = $form_state->getValues();
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#access' => $d->prompt_subject,
    ];

    $form['body'] = array(
        '#type' => 'text_format',
        '#title' => t('Message'),
        '#default_value' => @$values['body'],
        '#format' => $input_format,
        '#access' => $d->prompt_body,
      );


    $form['send'] = array(
        '#type' => 'radios',
        '#title' => t('Send Email'),
        '#options' => array('send' => 'email to users',
          'test' => 'emails to me (test mode)'),
        '#default_value' => 'test',
        '#access' => !$email_override
      );

    $form['max'] = array(
      '#type' => 'textfield',
      '#title' => 'Only send first',
      '#description' => 'In test mode only, limits the number of messages to send',
      '#default_value' => 1,
      '#size' => 3,
    );

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Email'),
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $test_send = $form_state->getValue('send') ==  'test';
    $email = $test_send ? $user->getEmail() : '';
    $max = $test_send ? $form_state->getValue('max') : 0;

    /** @var EmailMerge $doc */
    $doc = DocManager::instance()->getDocument();
    $body = $form_state->getValue('body');
    $subject = $form_state->getValue('subject');
    $i = 0;
    if (!empty($body)) {
      // @TODO:  Figure out formatting and wysiwyg.
      //$body =  check_markup($form_state['values']['body']['value'],$form_state['values']['body']['format']);
    }
    $doc->sendMail($email, $max, $subject, $body);
  }

}