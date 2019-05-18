<?php

namespace Drupal\onehub\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\onehub\OneHubApi;

/**
 * Downloads form for the OneHub field formatter.
 */
class OneHubSelectDownloadForm extends FormBase {

  /**
   * The OneHub file for downloading.
   *
   * @var array
   */
  protected $file = [];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'onehub_select_download_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load the current viewed entity.
    $current_uri = \Drupal::request()->getRequestUri();
    $params = Url::fromUri("internal:" . $current_uri)->getRouteParameters();
    $entity_type = key($params);
    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($params[$entity_type]);

    // Load the fields from that entity.
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fields = $entityFieldManager->getFieldDefinitions($entity_type, $entity->bundle());

    // Get out of here if we are on a no fields on the entity.
    if (!isset($fields)) {
      return [];
    }

    // Check the fields and grab the onehub stuffs.
    $fieldz = [];
    foreach ($fields as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        if ($field_definition->getType() == 'onehub_select') {
          $fieldz = $entity->get($field_name)->getValue();
        }
      }
    }

    if (empty($fieldz)) {
      return [];
    }

    // Grab the files from the DB.
    $db = \Drupal::database();

    $folders = [];
    foreach ($fieldz as $field) {
      $folders[] = $field['folder'];
    }

    $results = $db->select('onehub', 'o')
      ->fields('o')
      ->condition('folder', $folders, 'IN')
      ->execute()
      ->fetchAll();

    // Render each file.
    foreach ($results as $delta => $result) {

      $form['download-' . $delta] = [
        '#type' => 'submit',
        '#name' => $result->oid,
        '#value' => t($result->filename),
        '#attributes' => ['class' => ['onehub-button']],
        '#prefix' => '<div class="field">',
        '#suffix' => '</div>',
      ];

      $this->file[$delta] = [
        'fid' => $result->oid,
        'filename' => $result->filename,
      ];
    }
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();

    // Parse out the delta form the triggering element.
    if (strpos($element['#id'], 'edit-download') !== FALSE) {
      $delta = str_replace('edit-download-', '', $element['#id']);
      $filename = $this->file[$delta]['filename'];
      $fid = $this->file[$delta]['fid'];
      $file = (new OneHubApi())->getFile($filename, $fid);
    }
  }
}
