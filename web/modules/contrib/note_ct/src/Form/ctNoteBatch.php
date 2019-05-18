<?php
/**
 * Created by PhpStorm.
 * User: dmitry
 * Date: 22.08.18
 * Time: 13:10
 */

namespace Drupal\note_ct\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ctNoteBatch extends FormBase {

  public function getFormId() {
    return 'ctNoteBatch';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['batch_function'] = [
      '#type' => 'radios',
      '#title' => $this->t('Operations'),
      '#options' => [
        'set_to_none' => $this->t('Set status to N/A'),
        'update_status' => $this->t('Update status'),
      ],
      '#default_value' => 'update_status',
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('BATCH'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nids = \Drupal::entityQuery('node')
      ->condition('type','note_ct')
      ->execute();
    $operations = [];

    // configuring operations. Batch is processing on single entity, so for example if you have 1000 entitys and 200 content types to process you need to create 1200 operations, not 2.
    if ($form_state->getValue('batch_function') == 'set_to_none') {
      foreach ($nids as $nid) {
        array_push($operations, ['NoteBatch::SetNoteToNone', ['nid' => $nid]]);
      }
    }

    $settings = \Drupal::config('note.note_ct.settings');
    if ($form_state->getValue('batch_function') == 'update_status') {
      foreach ($nids as $nid) {
        array_push($operations, ['NoteBatch::UpdateStatus', ['nid' => $nid, 'date' => $settings->get('date')]]);
      }
    }

    $batch = [
      'init_message' => t('Executing a batch...'),
      'operations' => $operations,
      'progress_message' => t('Processed @current out of @total.'),
      'file' => drupal_get_path('module', 'note_ct') . '/note_ct.batch.inc',
      'finished' => 'NoteBatch::BatchFinished',
    ];
    batch_set($batch);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $nids = \Drupal::entityQuery('node')
      ->condition('type','note_ct')
      ->execute();
    if (count($nids) == 0) {
      $form_state->setErrorByName('nids', $this->t('Note content type is empty.')  );
    }

  }
}
