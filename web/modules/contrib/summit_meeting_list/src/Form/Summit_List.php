<?php
/*
  * @file
  * Contains Drupal\summit_list\Form\Summit_List
*/
namespace  Drupal\summit_list\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/*
 * Defining a class to enhance its functionality
 */
class Summit_List extends FormBase{
  /*
   * Creating a function to get the form_id
   * As per Drupal Module Docuementation
   */
  public  function getFormId(){
    return 'summit_list_email_form';
  }
  public  function  buildForm(array $form, FormStateInterface $form_state){
   /*
    * [@InheritDoc]
    */
   $node = \Drupal::routeMatch()->getParameter('node');
   $nid = $node->nid->value;
   $form['email'] = array(
     '#title' => t('Email Address'),
     '#type' => 'textfield',
     '#description' => t('Meeting Updates will be sended to Email Provided!'),
     '#size' => 25,
//     '#placeholder' => 'abc@example.com',
     '#required' => TRUE,
   );
   $form['submit'] = array(
    '#type' => 'submit',
     '#title' => 'Submit',
     '#value' => t('Submit Email'),
   );
   $form['nid'] = array(
     '#type' => 'hidden',
     '#value' => $nid,
   );
    return $form;
  }
 /*
  * (@inheritdoc)
  */
  public  function validateForm(array &$form, FormStateInterface $form_state){
    $value=$form_state->getValue('email');
    if($value == !\Drupal::service('email.validator')->isValid($value)){
     $form_state->setErrorByName('email',t('The Entered email %mail is not Valid!',array('%mail'=>$value)));
    }
  }


  public  function  submitForm(array &$form , FormStateInterface $form_state){
    /*
     * (@inheritdoc)
     */
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    db_insert('summit_list')->fields(array('mail' => $form_state-> getValue('email'),
      'nid' => $form_state->getValue('nid'),
//      'uid' => $form_state->getValue('uid'),
      'uid' => $user->id(),
      'created' => time(),
      ))
      ->execute();
    drupal_set_message(t('Your Email  has successfully submitted'));
//      drupal_set_message(t('Form Submitted Successfully'));
  }
}
