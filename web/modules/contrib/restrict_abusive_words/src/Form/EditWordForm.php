<?php

/**
 * @file
 * Contains \Drupal\restrict_abusive_words\Form\EditWordForm.
 */

namespace Drupal\restrict_abusive_words\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Contribute form.
 */
class EditWordForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'restrict_abusive_words_admin_edit_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $wid = NULL) {
        
        if (!isset($wid) || !is_numeric($wid)) {
          drupal_set_message(t('The restrict_abusive_words ID of the word or phrase you are trying to edit is missing or is not a number.'), 'error');
          return new RedirectResponse(\Drupal::url('restrict_abusive_words.add_words'));
        }

        $word = _restrict_abusive_words_get_words_list($wid);
        $form = array();
        $form['id'] = array(
          '#type' => 'hidden',
          '#value' => $wid,
        );

        $form['words'] = array(
          '#type' => 'textfield',
          '#title' => t('Word or phrase to Edit'),
          '#default_value' => $word[$wid],
          '#description' => t('Enter the word or phrase you want to update.'),
          '#size' => 50,
          '#maxlength' => 255,
          '#required' => TRUE,
        );

        $form['update_word'] = array(
          '#type' => 'submit',
          '#value' => t('Save word'),
        );
        $form['cancel'] = array(
          '#type' => 'submit',
          '#value' => t('Cancel'),
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $connection = Database::getConnection();
        if(!empty($form_state->getValue('id')) && !empty($form_state->getValue('id'))) {
            
            $query = $connection->update('restrict_abusive_words');
            $query->condition('id', $form_state->getValue('id'));
            $query->fields(array('words' => $form_state->getValue('words')));

            // Execute the statement
            $data = $query->execute();

            drupal_set_message(t('Added word: %word', array('%word' => $row->words)));
        }
        $form_state->setRedirect('restrict_abusive_words.list_words');
    }
}
