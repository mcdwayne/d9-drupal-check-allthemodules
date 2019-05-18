<?php

namespace Drupal\block_upload\Form;

use Drupal\block_upload\BlockUploadBuild;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;

/**
 * Block upload form.
 */
class BlockUploadPluploadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL, $buid = 0) {
    $submit = FALSE;
    $field_name = \Drupal::state()->get('block_upload_' . $buid . '_field') ?: '';
    $type_name = explode('.', $field_name)[0];
    $field_name = explode('.', $field_name)[1];
    $field_limit = FieldStorageConfig::loadByName($type_name, $field_name);
    $fields_info = FieldConfig::loadByName($type_name, $node->getType(), $field_name);
    if ($node->get($field_name)) {
      $field_files_exists = count($node->get($field_name));
    }
    else {
      $field_files_exists = 0;
    }
    if (\Drupal::currentUser()->hasPermission('block remove') && $field_files_exists > 0) {
      $title_remove_form = $this->t('Remove files');
      $form['remove_files_title'] = ['#markup' => '<h3>' . $title_remove_form . '</h3>'];
      $form['remove_files'] = BlockUploadBuild::blockUploadRemoveForm($field_limit, $node, $field_name);
      $submit = TRUE;
    }
    if (($field_limit->getCardinality() > $field_files_exists || $field_limit->getCardinality() == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)) {
      $title_upload_form = t('Upload file');
      $form['upload_files_title'] = ['#markup' => '<h3>' . $title_upload_form . '</h3>'];
      $form['block_upload_file'] = [
        '#type' => 'plupload',
        '#upload_validators' => BlockUploadBuild::blockUploadGetValidators($field_name, $fields_info, $node),
      ];
      $submit = TRUE;
      $settings = \Drupal::state()->get('block_upload_' . $buid . '_settings') ?: [];
    }
    else {
      $form[] = [
        '#type' => 'item',
        '#description' => t('Exceeded limit of files'),
      ];
    }
    if ($submit) {
      $module_path = drupal_get_path('module', 'block_upload');
      $form['#attached']['library'][] = 'block_upload/table-file';
      $form['block_upload_nid'] = [
        '#type' => 'textfield',
        '#access' => FALSE,
        '#value' => $node->get('nid')->getValue()['0']['value'],
      ];
      $form['block_upload_node_type'] = [
        '#type' => 'textfield',
        '#access' => FALSE,
        '#value' => $node->getType,
      ];
      $form['buid'] = [
        '#type' => 'value',
        '#value' => $buid,
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => t('Save'),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('block_upload_file') as $uploaded_file) {
      if ($uploaded_file['status'] != 'done') {
        $form_state->setErrorByName('block_upload_file', t("Upload of %filename failed.", ['%filename' => $uploaded_file['name']]));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * Saves files uploaded via plupload form.
   *
   * Example taken from plupload_test_submit();
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $nid = $values['block_upload_nid'];
    $buid = $values['buid'];
    $field_name = explode('.', \Drupal::state()->get('block_upload_' . $buid . '_field') ?: '')[1];
    $node = Node::load($nid);
    if (isset($values['remove_files'])) {
      if (array_filter($values['remove_files'])) {
        BlockUploadBuild::blockUploadDeleteFiles($node, $field_name, $values);
      }
    }
    if (isset($values['block_upload_file'])) {
      $uid = \Drupal::currentUser();
      $destination = \Drupal::config('system.file')->get('default_scheme') . '://plupload';
      file_prepare_directory($destination, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
      foreach ($values['block_upload_file'] as $uploaded_file) {
        $file_uri = file_stream_wrapper_uri_normalize($destination . '/' . $uploaded_file['name']);

        // Move file without creating a new 'file' entity.
        $uri = file_unmanaged_move($uploaded_file['tmppath'], $file_uri);

        $file = File::Create([
          'uri' => $uri,
          'uid' => $uid->id(),
        ]);
        $file->save();
        // @todo: When https://www.drupal.org/node/2245927 is resolved,
        // use a helper to save file to file_managed table
        $node->get($field_name)->appendItem($file);
        drupal_set_message(t('File was successfully uploaded!'));
      }
    }
    $node->save();
  }

}
