<?php

namespace Drupal\entity_delete\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityDeleteConfirmationForm.
 *
 * @package Drupal\entity_delete\Form
 */
class EntityDeleteConfirmationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_delete_confirmation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $param = NULL) {
    $form['title'] = [
      '#markup' => $this->t('<h1>Are you sure you want to delete?</h1><br>'),
    ];
    $form['delete'] = [
      '#type' => 'submit',
      '#value' => 'Confirm',
    ];
    $form['cancel'] = [
      '#type' => 'submit',
      '#value' => 'Cancel',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = \Drupal::request()->query;
    $entity_type = $query->get('entity_type');
    $bundle = $query->get('bundle');

    if ($form_state->getValue('op') == 'Cancel') {
      $form_state->setRedirect('entity_delete.entity_delete_bulk');
    }
    else {
      $success_message = FALSE;
      // To delete log entries.
      if ($entity_type == 'watchdog' && $bundle == 'all') {
        $conn = Database::getConnection();
        $truncate = $conn->truncate('watchdog');
        $truncate->execute();
        drupal_set_message($this->t('Log Entries Cleared Successfully'));
      }
      else {
        $bundle_type = '';
        if ($entity_type == 'users') {
          $entity_type = 'user';
        }
        else {
          if ($entity_type == 'file_managed') {
            $entity_type = 'file';
          }
        }
        // Build entity query.
        $entity_query = \Drupal::entityQuery($entity_type);
        $batch = [
          'title' => $this->t('Deleting @entity_type...', [
            '@entity_type' => $entity_type,
          ]),
        // Error Message.
          'error_message' => $this->t('Error!'),
          'finished' => '\Drupal\entity_delete\DeleteEntity::deleteEntityFinishedCallback',
        ];
        // To delete User(s)
        if ($entity_type == 'user') {
          $entity_query->condition('uid', [0, 1], 'NOT IN');
        }
        // To delete Remaining entities.
        else {
          // Get entity bundle.
          $exclude_entities = ['file', 'comment', 'user', 'watchdog'];
          if (!in_array($entity_type, $exclude_entities)) {
            $manager = \Drupal::entityDefinitionUpdateManager();
            $entity_type_load = $manager->getEntityType($entity_type);
            $entity_keys = $entity_type_load->getKeys();
            $bundle_type = $entity_keys['bundle'];
            if ($bundle_type) {
              if ($bundle != 'all') {
                $entity_query->condition($bundle_type, $bundle);
              }
            }
          }
        }
        $entity_ids = $entity_query->execute();
        if (count($entity_ids) > 0) {
          // Chunk entity ids.
          $batch_ids = array_chunk($entity_ids, 25);
          $count_ids = 0;
          foreach ($batch_ids as $delete_ids) {
            $count_ids += count($delete_ids);
            $batch['operations'][] = [
              '\Drupal\entity_delete\DeleteEntity::deleteEntity',
              [
                $delete_ids,
                $count_ids,
                count($entity_ids),
                $entity_type,
                $bundle,
              ],
            ];
          }
          batch_set($batch);
          $success_message = TRUE;
        }
        if ($success_message) {
          $form_state->setRedirect('entity_delete.entity_delete_bulk');
        }
        else {
          drupal_set_message($this->t('no @entity(s) found to delete.', [
            '@bundle' => $bundle,
            '@entity' => $entity_type,
          ]), 'warning');
          $form_state->setRedirect('entity_delete.entity_delete_bulk');
        }
      }
    }
  }

}
