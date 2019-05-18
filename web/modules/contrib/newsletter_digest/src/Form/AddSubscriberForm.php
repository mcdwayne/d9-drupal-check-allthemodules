<?php
/** 
 * @file
 * @author  Er. Sandeep Jangra
 * Contains \Drupal\newsletter_digest\Form\AddSubscriberForm.
 */
namespace Drupal\newsletter_digest\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;

class AddSubscriberForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'newsletter_digest_add_subscriber_form';
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
    $form['first_name'] = array(
      '#type' => 'textfield',
      '#title' => 'First Name',
      '#required' => TRUE,
    );
    $form['last_name'] = array(
      '#type' => 'textfield',
      '#title' => 'Last Name',
      '#required' => TRUE,
    );
    $form['email'] = array(
      '#type' => 'textfield',
      '#title' => 'Email',
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

      if (empty($form_state->getValue('first_name'))) {
        $form_state->setErrorByName('first_name', $this->t('First name field can not be blank'));
      }
      if (empty($form_state->getValue('last_name'))) {
        $form_state->setErrorByName('last_name', $this->t('Last name field can not be blank'));
      }
      if (empty($form_state->getValue('email'))) {
        $form_state->setErrorByName('email', $this->t('Email field can not be blank'));
      }
      if (!valid_email_address($form_state->getValue('email'))) {
        $form_state->setErrorByName('email', $this->t('Please enter valid email address'));
      }
      if (empty($form_state->getValue('category'))) {
        $form_state->setErrorByName('category', $this->t('Please select category'));
      }

    }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
   $conn = Database::getConnection();
   $conn->insert('newsletter_digest_subscriber')->fields(
     array(
        'first_name' => $form_state->getValue('first_name'),
        'last_name' => $form_state->getValue('last_name'),
        'email' => $form_state->getValue('email'),
        'category_id' => $form_state->getValue('category'),
        'created' => time(),
       )
     )->execute();

    drupal_set_message($this->t('A New subscriber " @first_name @last_name " has been added successfully!', array('@first_name' => $form_state->getValue('first_name'), '@last_name' => $form_state->getValue('last_name'))));

    }
}
