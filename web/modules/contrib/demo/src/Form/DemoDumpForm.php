<?php

namespace Drupal\demo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class DemoDumpForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'demo_dump_form';
  }

  /**
   * form to create database snapshots.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    $form['dump']['filename'] = [
      '#title' => t('Name'),
      '#type' => 'textfield',
      '#autocomplete_path' => 'demo/autocomplete',
      '#required' => TRUE,
      '#maxlength' => 128,
      '#description' => t('Allowed characters: a-z, 0-9, dashes ("-"), underscores ("_") and dots.'),
    ];
    $form['dump']['description'] = [
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#rows' => 2,
      '#description' => t('Leave empty to retain the existing description when replacing a snapshot.'),
    ];
    $form['dump']['tables'] = [
      '#type' => 'value',
      '#value' => demo_enum_tables(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Create'),
    ];

    return $form;
  }

  /**
   * Validate form values.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue(['confirm'])) {
      $fileconfig = demo_get_fileconfig($form_state->getValue([
        'dump',
        'filename',
      ]));
      if (file_exists($fileconfig['infofile']) || file_exists($fileconfig['sqlfile'])) {
        $form_state->set(['demo', 'dump_exists'], TRUE);
        $form_state->setErrorByName('dump[filename]', t('File exists'));
        $form_state->setRebuild(TRUE);
      }
    }
  }

  /**
   * create the database.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($fileconfig = _demo_dump($form_state->getValue(['dump']))) {
      drupal_set_message(t('Snapshot %filename has been created.', [
        '%filename' => $form_state->getValue(['dump', 'filename']),
      ]));
    }
    $form_state->setRedirect('demo.manage_form');
  }

}
