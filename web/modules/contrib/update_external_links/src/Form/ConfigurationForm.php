<?php

namespace Drupal\update_external_links\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form constructor for update_external_links_input_form.
 */
class ConfigurationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_external_links_input_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $content_types = node_type_get_names();
    $form['description'] = array(
      '#type' => 'markup',
      '#markup' => $this->t('<p><strong>Please select content type you want to update for external links to be opened in new tab.</strong></p>'),
    );
    foreach ($content_types as $key => $value) {
      $form[$key] = array(
        '#type' => 'checkbox',
        '#title' => $value,
        '#options' => $value,
      );
    }
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Update External Links'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate submitted form data.
    $form_values = $form_state->getValues();
    $types = node_type_get_names();

    foreach ($form_values as $key => $values) {
      if (array_key_exists($key, $types)) {
        if ($values == 1) {
          $content_types[] = $key;
        }
      }
    }
    if (empty($content_types)) {
      $form_state->setErrorByName('select_content_types', $this->t('Seems you have not selected any content type. Please select atleast one content type you want to update first.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle submitted form data.
    $selected_content_types = array();
    $form_values = $form_state->getValues();
    $types = node_type_get_names();
    foreach ($form_values as $key => $values) {
      if (array_key_exists($key, $types)) {
        if ($values == 1) {
          $selected_content_types[] = $key;
        }
      }
    }
    $node_ids = $this->updateExternalLinksSelectedNid($selected_content_types);

    if (!count($node_ids)) {
      drupal_set_message($this->t("Seems You don't currently have any nodes with the selected content types."));
    }
    // Execute the function named update_external_links_batch_start.
    else {
      $batch = $this->updateExternalLinksBatchStart($node_ids);
      batch_set($batch);
    }
  }

  /**
   * Utility function- simply querie and loads all nid of selected content type.
   */
  public function updateExternalLinksSelectedNid(array $content_types_name) {
    $query = db_select('node', 'n');
    $query->innerjoin('node__body', 'b', 'n.nid = b.entity_id');
    $query->fields('n', array('nid'))->condition('b.body_value', '', '<>')->condition('n.type', $content_types_name, 'IN')->orderBy('n.nid', 'ASC');
    $nid = $query->execute()->fetchCol();
    return $nid;
  }

  /**
   * Batch process start function.
   */
  public function updateExternalLinksBatchStart($node_ids) {
    $num_operations = count($node_ids);

    if ($num_operations > 0) {
      $operations = array();
      for ($i = 0; $i < $num_operations; $i++) {
        $nid = $node_ids[$i];
        $operations[] = array(
          'updateExternalLinksProcess_1',
          array(
            $nid,
            t('(Operation @operation)', array('@operation' => $num_operations)),
          ),
        );
      }
    }

    $batch = array(
      'operations' => $operations,
      'finished' => 'updateExternalLinksFinished',
      'title' => $this->t('Processing External Links'),
      'init_message' => $this->t('Update external links process is starting.'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('Update External Link has encountered an error.'),
      'file' => drupal_get_path('module', 'update_external_links') . '/batch.inc',
    );
    return $batch;
  }

}
