<?php

namespace Drupal\photoshelter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PhotoShelterDeleteForm.
 *
 * @package Drupal\photoshelter\Form
 */
class PhotoShelterDeleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photoshelter_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => t('Delete all sync data'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operations = [];
    // Delete media entities.
    $query = \Drupal::entityQuery('media');
    $query->condition('bundle', 'ps_image');
    $mids = $query->execute();
    if (!empty($mids)) {
      $batch_array = array_chunk($mids, 20);
      foreach ($batch_array as $mids_chunk) {
        $operations[] = [
          'photoshelter_delete_media',
          [$mids_chunk],
        ];
      }
    }

    // Delete media entities.
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', '%' . db_like('ps_') . '%', 'LIKE');
    $tids = $query->execute();
    if (!empty($tids)) {
      $batch_array = array_chunk($tids, 20);
      foreach ($batch_array as $tids_chunk) {
        $operations[] = [
          'photoshelter_delete_terms',
          [$tids_chunk],
        ];
      }
    }

    if (!empty($operations)) {
      $batch = array(
        'title' => t('PhotoShelter delete data'),
        'operations' => $operations,
        'finished' => 'photoshelter_delete_finished',
        'file' => drupal_get_path('module', 'photoshelter') . '/photoshelter.batch.inc',
      );

      batch_set($batch);
    }

    // Clear queue.
    $queue_name_array = [
      'photoshelter_syncnew_collection',
      'photoshelter_syncnew_gallery',
      'photoshelter_syncnew_photo',
    ];
    foreach ($queue_name_array as $name) {
      $queue = \Drupal::queue($name);
      $queue->deleteQueue();
    }

    \Drupal::messenger()->addMessage($this->t('All PhotoShelter data is deleted.'));

    $config = \Drupal::service('config.factory')->getEditable('photoshelter.settings');
    $config->set('last_sync', 'Never');
    $config->save();

  }

}
