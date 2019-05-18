<?php
/**
 * @file
 * Contains \Drupal\personal_notes\Form\pn_deletenote.
 */
namespace Drupal\personal_notes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class pn_deletenote extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'personal_notes_dlet_content';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!\Drupal::currentUser()->isAnonymous()) {
      $this->messenger()->addMessage($this->t('Select the notes to be deleted and submit - this cannot be reversed.'), 'status');

      $results = _personal_notes_fetch_content_db();			//	get the notes for this user
      $checkboxes = array();
      foreach ($results as $fields) {							//	loop through the user's notes to build checkboxes
        $checkboxes[$fields->notenum] = $fields->title . t(' - ') .
          substr($fields->note, 0, 9);							//	display { title - note [1..10] }
      }

      $form["delete"] = array(
        '#type' => 'checkboxes',
        '#options' => $checkboxes,
      );

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Delete Selected Pers. Notes'),
        '#button_type' => 'primary',
      );
      return $form;
    }
    else {
      $this->messenger()->addMessage($this->t('Please log on in order to delete your notes.'), 'status');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $num_deleted = 0;
   	$delete = $form_state->getValues()['delete'];
    foreach ($delete as $key => $value) {
      if (!empty($value)) {
        \Drupal::database()->delete('personal_notes_notes')
          ->condition('notenum', $value)
          ->execute();
        ++$num_deleted;
      }
    }
    $wasWere = ($num_deleted == 1) ? 'was ' . $num_deleted . ' note' :		//	make our message grammatically correct
      'were ' . $num_deleted . ' notes';
    $this->messenger()->addMessage($this->t('There ' . $wasWere . ' removed.'), 'status');
  }

}
