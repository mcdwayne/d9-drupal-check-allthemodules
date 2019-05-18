<?php

namespace Drupal\shrinktheweb\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ShrinkTheWebLogForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shrinktheweb_log';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();

      $log_records = array();

      // format the tableselect header
      $header = array(
        'domain' => array('data' => t('Domain'), 'field' => 'sl.stw_domain'),
        'hash' => array('data' => t('Hash'), 'field' => 'sl.stw_hash'),
        'timestamp' => array('data' => t('Timestamp'), 'field' => 'sl.stw_timestamp'),
        'capturedon' => array('data' => t('Captured on'), 'field' => 'sl.stw_capturedon'),
        'quality' => array('data' => t('Quality'), 'field' => 'sl.stw_quality'),
        'full' => array('data' => t('Full'), 'field' => 'sl.stw_full'),
        'xmax' => array('data' => t('Xmax'), 'field' => 'sl.stw_xmax'),
        'ymax' => array('data' => t('Ymax'), 'field' => 'sl.stw_ymax'),
        'nrx' => array('data' => t('nrX'), 'field' => 'sl.stw_nrx'),
        'nry' => array('data' => t('nrY'), 'field' => 'sl.stw_nry'),
        'invalid' => array('data' => t('Invalid'), 'field' => 'sl.stw_invalid'),
        'stwerrcode' => array('data' => t('STW error code'), 'field' => 'sl.stw_stwerrcode'),
        'error' => array('data' => t('Error'), 'field' => 'sl.stw_error'),
        'errcode' => array('data' => t('Error code'), 'field' => 'sl.stw_errcode'),
      );

      // get the log records
      $result = \Drupal::database()->select('shrinktheweb_log', 'sl')
        ->fields('sl')
        ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
        ->limit(25)
        ->addTag('shrinktheweb_log_records')
        ->execute();

      foreach ($result as $record) {
        $log_records[$record->stw_siteid] = array(
          'domain' => $record->stw_domain,
          'hash' => $record->stw_hash,
          'timestamp' => date("M d, Y, H:i:s", $record->stw_timestamp),
          'capturedon' => date("M d, Y, H:i:s", $record->stw_capturedon),
          'quality' => $record->stw_quality,
          'full' => $record->stw_full,
          'xmax' => $record->stw_xmax != 0 ? $record->stw_xmax : 'Not set',
          'ymax' => $record->stw_ymax != 0 ? $record->stw_ymax : 'Not set',
          'nrx' => $record->stw_nrx,
          'nry' => $record->stw_nry,
          'invalid' => $record->stw_invalid,
          'stwerrcode' => $record->stw_stwerrcode,
          'error' => $record->stw_error,
          'errcode' => $record->stw_errcode,
        );
      }

      $form['log_records'] = array(
        '#type' => 'tableselect',
        '#empty' => t('The log is empty.'),
        '#header' => $header,
        '#options' => $log_records,
      );

      $form['pager'] = array(
        '#type' => 'pager',
      );

      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Delete selected records'),
        '#button_type' => 'primary',
      );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $log_recs = array_filter($form_state->getValue('log_records'));

    if (!$log_recs)
      $form_state->setErrorByName('log_records', t('Please, select a log record'));

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $log_recs = array_filter($form_state->getValue('log_records'));
    $num_deleted = \Drupal::database()->delete('shrinktheweb_log')
      ->condition('stw_siteid', $log_recs, 'IN')
      ->execute();
    drupal_set_message(t('@num_deleted log records deleted successful', array('@num_deleted' => $num_deleted)));

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['shrinktheweb.log'];
  }

}
