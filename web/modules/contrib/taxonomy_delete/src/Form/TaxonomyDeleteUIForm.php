<?php

namespace Drupal\taxonomy_delete\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TaxonomyDeleteUIForm.
 *
 * @package Drupal\taxonomy_delete\Form
 */
class TaxonomyDeleteUIForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_delete_ui_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $vocabulary = [];
    $vocabulary_items = taxonomy_vocabulary_get_names();
    foreach ($vocabulary_items as $item) {
      $vocabulary[$item] = ucfirst(str_replace("_", " ", $item));
    }

    $form['vocabulary'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Vocabularies'),
      '#options' => $vocabulary,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete Terms'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_vocabulary = array_filter($form_state->getValue('vocabulary'));
    foreach ($selected_vocabulary as $vid) {
      // Batch process the vocabulary to delete the terms in it.
      $batch = [
        'operations' => [
          [
            '\Drupal\taxonomy_delete\TaxonomyDeleteBatch::processVocabulary',
            [$vid],
          ],
        ],
        'finished' => '\Drupal\taxonomy_delete\TaxonomyDeleteBatch::finishProcess',
      ];
      batch_set($batch);
    }
  }

}
