<?php

/**
 * @file
 * Contains \Drupal\private_notes\Form\PrivateNotesAddForm.
 */

namespace Drupal\private_notes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

class PrivateNotesAddForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormID() {
        return 'pvtnotesadd_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        // attached the css file
        $form['#attached']['library'][] = 'private_notes/private_notes.view';
        /* Getting the current page node id */
        $current_node = \Drupal::request()->attributes->get('node');
        $current_nid = $current_node->id();
        // Show notes div only if notes are there.
        $notes_content = $this->private_notes_fetch_content($current_nid);
        if ($notes_content) {
            $form['view_notes'] = array(
                '#prefix' => '<div class="private-notes-area">',
                '#markup' => $notes_content,
                '#suffix' => '</div><br/>',
            );
        }
        $form['note_body'] = array(
            '#type' => 'textarea',
            '#title' => t('Note'),
            '#cols' => 50,
            '#rows' => 5,
            '#required' => TRUE,
            '#attributes' => array('maxlength' => 512),
            '#description' => t('Your note should not exceed 512 characters.'),
        );
        $form['current_nid'] = array(
            '#type' => 'hidden',
            '#value' => $current_nid,
        );
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save My Note'),
        );
        
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        if ($form_state->getValue('note_body') == '') {
            $form_state->setErrorByName('note_body', $this->t('Please enter private notes.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $user = \Drupal::currentUser();
        $uid = $user->id();
        $current_nid = $form_state->getValue('current_nid');
        $note_body = $form_state->getValue('note_body');
        $query = \Drupal::database()->insert('private_notes');
        $query->fields([
            'uid',
            'nid',
            'note',
            'created'
        ]);
        $query->values([
            $uid,
            $current_nid,
            $note_body,
            REQUEST_TIME
        ]);
        $query->execute();
        drupal_set_message('message' . ': ' . 'Your note has been saved');
    }

    /**
     * Fetch notes from private_notes table for current node id.
     * {@inheritdoc}
     */
    function private_notes_fetch_content($current_nid) {
        $query = \Drupal::database()->select('private_notes', 'pn');
        $query->fields('pn', ['note', 'pnid', 'uid', 'created']);
        $query->condition('nid', $current_nid);
        $results = $query->execute()->fetchAll();
        /*
          return array(
          '#theme' => 'private_notes',
          '#results' => $results
          );
         * */
        $html = '<ul>';
        $results = array_reverse($results);
        foreach ($results as $result) {
            $account = \Drupal\user\Entity\User::load($result->uid);
            $user_name = $account->get('name')->value;
            $html .= '<li>
                    <div class="PrivateNotes">
                    <div class="title">' . $user_name . ' on ' . format_date($result->created, 'custom', 'd-m-Y H:i:s T') . ' </div>
                    <div class="note">' . SafeMarkup::checkPlain($result->note) . ' </div>
                    <hr class="sep"></hr></div>
                 </li>';
        }
        $html .="</ul>";
        return $html;
    }
}
