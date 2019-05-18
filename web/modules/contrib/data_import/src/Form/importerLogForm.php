<?php

/**
 * @file
 * Contains \Drupal\data_import\Form\importerDeleteForm.
 */
 
 namespace Drupal\data_import\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;

/**
 * Defines a confirmation form for deleting mymodule data.
 */
class importerLogForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'importer_log_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param int $id
   *   (optional) The ID of the item to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {#

    $form['filters'] = array(
        '#type' => 'fieldset',
        '#title' => t('Filter log messages'),
        '#collapsible' => TRUE,
        '#collapsed' => !isset($_SESSION['data_import_log_filter']),
      );
    
      $form['filters']['process_id'] = array(
        '#title' => t('Process ID :'),
        '#type' => 'textfield',
        '#size' => 20,
        '#default_value' => isset($_SESSION['data_import_log_filter']['process_id']) ? $_SESSION['data_import_log_filter']['process_id'] : ''
      );
    
      $importers = data_import_load_all_importers();
      $form['filters']['importer_id'] = array(
        '#title' => t('Importer ID :'),
        '#type' => 'select',
        '#options' => array_merge(['all' => t('All')], array_map('importers_map', $importers)),
        '#default_value' => isset($_SESSION['data_import_log_filter']['importer_id']) ? $_SESSION['data_import_log_filter']['importer_id'] : 'all'
      );
    
      $form['filters']['actions'] = array(
        '#type' => 'actions',
        '#attributes' => array('class' => array('container-inline')),
      );
    
      $form['filters']['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Filter'),
      );
    
      $form['filters']['actions']['reset'] = array(
        '#type' => 'submit',
        '#value' => t('Reset')
      );

      $rows = array();
    
      $sorting = isset($_GET['sort']) ? $_GET['sort'] : 'desc';
    
      if(isset($_GET['importer_id'])) {
        $_SESSION['data_import_log_filter']['importer_id'] = $_GET['importer_id'];
      }
    
      $header = array(
        array('data' => t('Process Id'), 'field' => 'log.process_id'),
        array('data' => t('Importer Id'), 'field' => 'log.importer_id'),
        array('data' => t('Date'), 'field' => 'log.timestamp', 'sort' => 'desc'),
        t('Message'),
        array('data' => t('Status'), 'field' => 'log.status'),
      );
    
      $query = db_select('data_import_log', 'log')->extend('Drupal\Core\Database\Query\PagerSelectExtender')->extend('Drupal\Core\Database\Query\TableSortExtender');
      $query
        ->fields('log', array('importer_id', 'process_id', 'status', 'timestamp', 'message'));
    
      if (isset($_SESSION['data_import_log_filter'])) {
        foreach ($_SESSION['data_import_log_filter'] as $key => $value) {
          if ($value)
            $query->condition($key, $value, '=');
        }
      }
    
      $result = $query
        ->limit(50)
        ->orderByHeader($header)
        ->orderBy('id', $sorting)
        ->execute();
    
      foreach ($result as $log) {
        $rows[] = array('data' =>
          array(
            $log->process_id,
            $log->importer_id,
            format_date($log->timestamp, 'short'),
            base64_decode(utf8_decode($log->message)),
            $log->status,
          ),
        );
      }
    
      $form['data_import_log_table'] = array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => t('No log messages available.'),
      );

      $form['data_import_log_pager'] = array('#type' => 'pager');
    
      return $form;


  }



/**
 * Form submission handler for data_import_log_filter_form().
 *
 */
 public function submitForm(array &$form, FormStateInterface $form_state){
    $op = $form_state->getValue('op')->render();

    // Reset query params
    if(isset($_GET['importer_id'])) unset($_GET['importer_id']);
  
    switch ($op) {
      case t('Filter'):
        $_SESSION['data_import_log_filter']['process_id'] = $form_state->getValue('process_id');
        $_SESSION['data_import_log_filter']['importer_id'] = $form_state->getValue('importer_id') == 'all' ? '' : $form_state->getValue('importer_id');
        break;
      case t('Reset'):
        unset($_SESSION['data_import_log_filter']);
        break;
    }
  }
  

}