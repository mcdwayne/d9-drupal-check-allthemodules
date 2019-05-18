<?php

/**
 * @file
 * Contains \Drupal\private_notes\Form\PrivateNotesDeleteForm.
 */

namespace Drupal\private_notes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\SafeMarkup;

class PrivateNotesDeleteForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormID() {
        return 'pvtnotesdelete_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $results = $this->private_notes_fetch_contents();
        $options = array();
        // Loop through $results to build table rows.
        foreach ($results as $fields) {
            $options[$fields->pnid] = array(
                'note' => Unicode::truncate(SafeMarkup::checkPlain($fields->note), 100, TRUE, TRUE),
                'nid' => $fields->nid,
                'uid' => $fields->uid,
                'created' => format_date($fields->created),
            );
        }

        $header = array(
            'note' => t('Note'),
            'nid' => t('Node ID'),
            'uid' => t('User ID'),
            'created' => t('Created On'),
        );

        $form['delete'] = array(
            '#type' => 'tableselect',
            '#header' => $header,
            '#options' => $options,
            '#multiple' => TRUE,
            '#empty' => t('No any notes submitted by you.'),
        );

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Delete Notes'),
        );
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $deleteIds = $form_state->getValue('delete');
        $msg = '';
        foreach ($deleteIds as $key => $element) {
            if ($element) {
                $query = "DELETE FROM {private_notes} WHERE pnid=:pnid";
                db_query($query, array(':pnid' => $key));
                drupal_set_message('Deleting Note Id: ' . $key, 'status');
            }
        }
    }

    /**
     * {@inheritdoc}
     * 
     * Retrieve own notes to be deleted.
     */
    function private_notes_fetch_contents() {
        $user = \Drupal::currentUser();
        $uid = $user->id();
        $query = \Drupal::database()->select('private_notes', 'pn');
        $query->fields('pn', ['note', 'pnid', 'uid', 'nid', 'created']);
        $query->condition('uid', $uid, '=');
        $query->orderBy('pnid', 'ASC');
        $results = $query->execute();
        return $results;
    }

}
