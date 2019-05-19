<?php

/**
 * @file
 * Contains \Drupal\wisski_bulkedit\Form\FileUploadForm.
 */
   
namespace Drupal\wisski_bulkedit\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class FileUploadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wisski_bulkedit_update_form';
  }

  /**
   * {@inheritdoc}
   */
  function buildForm(array $form, FormStateInterface $form_state) {
    
    $storage = $form_state->getStorage();
    
    $file = $form_state->getValue('file', '');
    $bundle_id = $form_state->getValue('bundle', '');

    // we effectively have a two-step form triggered by ajax
    // first: upload file
    // second: define mappings
    $form['file'] = [
      '#type' => 'file',
      '#title' => 'CSV file',
      '#ajax' => [
        'callback' => '::ajaxUpdateMapping',
        'wrapper' => 'mapping_wrapper',
      ],
    ];

    $bundles = ['' => $this->t('- Select -')];
    foreach (entity_load_multiple('wisski_bundle') as $bid => $bundle) {
      $bundles[$bid] = $bundle->label();
    }
    
    $form['bundle'] = [
      '#type' => 'select',
      '#title' => 'Bundle',
      '#options' => $bundles,
      '#default_value' => $form_state->getValue('bundle', ''),
      '#ajax' => [
        'callback' => '::ajaxUpdateMapping',
        'wrapper' => 'mapping_wrapper',
      ],
    ];
    
    if (!isset($storage['header']) && $file) {
      $storage += $this->parseFile($file);
      $form_state->setStorage($storage);
    }

    $form['mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Column mappings'),
      '#prefix' => '<div id="mapping_wrapper">',
      '#suffix' => '</div>',
    ];
   

    $fields = ['' => $this->t('- None -')];
    if ($bundle_id) {
      $field_defs = \Drupal::entityManager()->getFieldDefinitions('wisski_individual', $bundle_id);
      foreach ($field_defs as $field_id => $def) {
        /** Drupal\Core\Field\FieldDefinitionInterface $def **/
        $fields[$field_id] = $def->getLabel();  // ->label() is not defined!
      }
    }
    
    if (isset($storage['header']) && $bundle_id) {
      $header = $storage['header'];
      foreach ($header as $i => $col) {
        $form['mapping']["col_$i"] = [
          '#type' => 'select',
          '#title' => Html::escape($col),
          '#options' => $fields,
          '#default_value' => $form_state->getValue("col_$i", ''),
        ];
      }
    }
    else {
      $form['mapping']['#description'] = $this->t('Please first select a CSV/TSV file and a bundle.');
    }
      
    // submit button
    $form['actions']['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];

    return $form;

  }
  
  
  public function ajaxUpdateMapping(array $form, FormStateInterface $form_state) {
    return $form['mapping'];
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }
  

  /** Parse the csv file and return header and table data
   * 
   * TODO: this is just a dirty hack for parsing a TSV with no options
   *       this could be done more professional
   *       maybe read into a db table and do the db import? => both should
   *       actually be merged
   */
  protected function parseFile($file) {
    $csv = file_get_contents($file);
    if (!$csv) return ['header' => NULL, 'table' => NULL];

    list($header, $data) = explode("\n", $csv, 2);

    $header = explode("\t", $header);
    $col_count = count($header);

    $table = [];
    foreach (explode("\n", $data) as $row) {
      $cells = explode("\t", $row);
      // make array same size as header
      $cells = array_pad($cells, $col_count, '');
      array_splice($cells, $col_count);
      $table[] = $cells;
    }
    
    return ['header' => $header, 'table' => $table];
  }

}
