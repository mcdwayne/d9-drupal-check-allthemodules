<?php

/**
 * @file
 * Contains \Drupal\restrict_abusive_words\Form\AdminSettingForm.
 */

namespace Drupal\restrict_abusive_words\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form;
use Drupal\Core\Database\Database;

/**
 * Contribute form.
 */
class AddWordsForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'restrict_abusive_words_admin_add_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['check_word'] = array(
          '#type' => 'textfield',
          '#title' => t('Look up for abusive word'),
          '#description' => t('Look up for abusive word.'),
          '#maxlength' => 60,
          '#autocomplete_path' => 'admin/config/content/restrict_abusive_words/autocomplete',
        );

        $form['words_list'] = array(
          '#type' => 'textarea',
          '#title' => t('Words'),
          '#description' => t("Enter a word or phrase you want to restrict as abusive. You can enter multiple word by adding more word on a new line."),
          '#required' => TRUE,
        );

        $form['save_wordlist'] = array(
          '#type' => 'submit',
          '#value' => t('Add abusive word'),
        );
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

        $words_list = explode("\n", $form_state->getValue('words_list'));
        $words = array_filter(array_map('trim', $words_list), 'strlen');
        foreach ($words as $word) {
            $search_string = _restrict_abusive_words_get_words_list();
            $check_word = _restrict_abusive_words_exists_words($search_string, $word);
            if ($check_word) {
              $form_state->setErrorByName('words_list', t('@word is already exist', array('@word' => $word)));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        $conn = Database::getConnection();
        $words_list = explode("\n", $form_state->getValue('words_list'));
        $words = array_filter(array_map('trim', $words_list), 'strlen');
        foreach ($words as $word) {
            $conn->insert('restrict_abusive_words')->fields(
              array(
                'words' => $word,
              )
            )->execute();
            drupal_set_message(t('Added word: %word', array('%word' => $row->words)));
        }
        $form_state->setRedirect('restrict_abusive_words.list_words');
    }

}
