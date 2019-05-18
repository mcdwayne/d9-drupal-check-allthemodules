<?php

/**
 * @file
 * Contains \Drupal\optimizedb\Form\OptimizedbListTablesForm.
 */

namespace Drupal\optimizedb\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Operations with tables.
 */
class OptimizedbListTablesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'optimizedb_list_tables_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $headers = array(
      'name' => array(
        'data' => $this->t('Table name'),
      ),
      'size' => array(
        'data' => $this->t('Table size'),
        'field' => 'size',
        'sort' => 'desc',
      ),
    );

    $tables = _optimizedb_tables_list();

    $sort = tablesort_get_sort($headers);

    usort($tables, function($a, $b) use ($sort) {
      if ($sort == 'asc') {
        return $a['size_byte'] > $b['size_byte'];
      }

      return $a['size_byte'] < $b['size_byte'];
    });

    $rows = array();

    // Messages status execute operation.
    optimizedb_operation_messages($form);

    foreach ($tables as $table) {
      // Parameter "size_byte" us only needed to sort, now his unit to remove.
      unset($table['size_byte']);

      $rows[$table['name']] = $table;
    }

    if (\Drupal::database()->driver() == 'mysql') {
      $form['operations'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Operations with tables:'),
      );

      $form['operations']['check_tables'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Check tables'),
      );

      $form['operations']['repair_tables'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Repair tables'),
      );

      $form['operations']['optimize_tables'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Optimize tables'),
      );
    }

    $form['tables'] = array(
      '#type' => 'tableselect',
      '#header' => $headers,
      '#options' => $rows,
      '#empty' => $this->t('No content available.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tables = $form_state->getValue('tables');
    $operation = '';

    $op = (string) $form_state->getValue('op');

    switch ($op) {
      // Checking the selected tables to find errors.
      case $this->t('Check tables'):
        $operation = 'CHECK TABLE';
        break;

      // Repair selected tables.
      case $this->t('Repair tables'):
        $operation = 'REPAIR TABLE';
        break;

      // Optimization of the selected tables.
      case $this->t('Optimize tables'):
        $operation = 'OPTIMIZE TABLE';
        break;
    }

    _optimizedb_list_tables_operation_execute($tables, $operation);
  }

}
