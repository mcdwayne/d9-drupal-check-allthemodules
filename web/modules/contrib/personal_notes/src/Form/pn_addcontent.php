<?php
/**
 * @file
 * Contains \Drupal\personal_notes\Form\pn_addcontent.
 */
namespace Drupal\personal_notes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class pn_addcontent extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'personal_notes_add_content';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!\Drupal::currentUser()->isAnonymous()) {
      $this->messenger()->addMessage($this->t('Enter your title and note text to add a new note.'), 'status');
      $form['note_title'] = array(
        '#type' => 'textfield',
        '#title' => t('Note Title'),
        '#size' => 30,
        '#maxlength' => 24,
        '#required' => TRUE,
      );
      $form['note_content'] = array(
        '#type' => 'textarea',
        '#title' => t('Note Content'),
        '#rows' => 3,
        '#cols' => 40,
        '#resizable' => TRUE,
        '#required' => TRUE,
      );
      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#button_type' => 'primary',
      );
      return $form;
    }
    else {
      $this->messenger()->addMessage($this->t('Please log on in order to add personal notes.'), 'status');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	$user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());	    //	Load the current user
	$uid= $user->get('uid')->value;												//	retrieve field data from that user
	$title = $form_state->getValues()['note_title'];
	$note = $form_state->getValues()['note_content'];
	$time = \Drupal::time()->getCurrentTime();

    $fields = [
      'uid' => $uid,
      'title' => t($title),
      'note' => t($note),
      'created' => $time,
    ];
    db_insert('personal_notes_notes')											//	Add new note to the database
      ->fields($fields)
      ->execute();
    $this->messenger()->addMessage($this->t('Your note was added.'), 'status');
  }
}
