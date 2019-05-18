<?php
/** 
 * @file
 * @author  Er. Sandeep Jangra
 * Contains \Drupal\newsletter_digest\Form\SendmailForm.
 */
namespace Drupal\newsletter_digest\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SendmailForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'newsletter_digest_send_mail_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', "nd_category");
    $tids = $query->execute();
    $terms = \Drupal\taxonomy\Entity\Term::loadMultiple($tids);
    $key = array();
    $val = array();
    foreach($terms as $term){
      $key[] = $term->id();
      $val[] = $term->getName();
    }
    $options = array_combine($key,$val);
    $form['last_sent'] = array(
       '#type' => 'markup',
       '#markup' => '12/04/2017',
       ); 
    $form['purpose'] = array(
      '#type' => 'textfield',
      '#title' => 'Purpose of newsletter',
      '#required' => TRUE,
    );
   $form['category'] = array (
      '#type' => 'select',
      '#title' => ('Select Subscriber Newsletter Category'),
      '#options' => $options,
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

   }
}
