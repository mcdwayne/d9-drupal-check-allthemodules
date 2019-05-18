<?php

namespace Drupal\block_upload\Form;

use Drupal\block_upload\BlockUploadBuild;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldConfig;
use Drupal\Component\Utility\Html;

/**
 * Configure book settings for this site.
 */
class BlockUploadForm extends FormBase {

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
        '#type' => 'managed_file',
        '#upload_location' => BlockUploadBuild::blockUploadGetUploadDestination($fields_info),
        '#upload_validators' => BlockUploadBuild::blockUploadGetValidators($field_name, $fields_info, $node),
      ];
      $submit = TRUE;
      $settings = \Drupal::state()->get('block_upload_' . $buid . '_settings') ?: [];

      if (isset($settings['alt']) && $settings['alt']) {
        $form['block_upload_' . $buid . '_alt'] = [
          '#type' => 'textfield',
          '#title' => t('Alt'),
        ];
      }
      if (isset($settings['title']) && $settings['title']) {
        $form['block_upload_' . $buid . '_title'] = [
          '#type' => 'textfield',
          '#title' => t('Title'),
        ];
      }
      if (isset($settings['desc']) && $settings['desc']) {
        $form['block_upload_' . $buid . '_desc'] = [
          '#type' => 'textfield',
          '#title' => t('Description'),
        ];
      }
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
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $nid = $values['block_upload_nid'];
    if (isset($values['block_upload_file']['fids']['0'])) {
      $file = $values['block_upload_file']['fids']['0'];
    }
    elseif (isset($values['block_upload_file']['0'])) {
      $file = $values['block_upload_file']['0'];
    }
    else {
      $file = '';
    }
    $buid = $values['buid'];
    $field_name = explode('.', \Drupal::state()->get('block_upload_' . $buid . '_field') ?: '')[1];
    $node = Node::load($nid);
    if (isset($values['remove_files'])) {
      if (array_filter($values['remove_files'])) {
        BlockUploadBuild::blockUploadDeleteFiles($node, $field_name, $values);
      }
    }
    if (!empty($values['block_upload_file']['fids']) || !empty($values['block_upload_file'])) {
      $new_file['target_id'] = $file;
      if (isset($values['block_upload_' . $buid . '_alt'])) {
        $alt = Html::escape($values['block_upload_' . $buid . '_alt']);
        $new_file['alt'] = $alt;
      }
      if (isset($values['block_upload_' . $buid . '_title'])) {
        $title = Html::escape($values['block_upload_' . $buid . '_title']);
        $new_file['title'] = $title;
      }
      if (isset($values['block_upload_' . $buid . '_desc'])) {
        $desc = Html::escape($values['block_upload_' . $buid . '_desc']);
        $new_file['description'] = $desc;
      }
      $node->get($field_name)->appendItem($new_file);
      drupal_set_message(t('File was successfully uploaded!'));
    }
    $node->save();
  }

}
