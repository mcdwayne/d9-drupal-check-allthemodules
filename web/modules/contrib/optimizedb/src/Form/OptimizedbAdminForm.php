<?php

/**
 * @file
 * Contains \Drupal\optimizedb\Form\OptimizedbAdminForm.
 */

namespace Drupal\optimizedb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Settings optimizedb module.
 */
class OptimizedbAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'optimizedb_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('optimizedb.settings');

    // Messages status execute operation.
    optimizedb_operation_messages($form);

    $form['executing_commands'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Executing commands manually'),
    );

    $form['executing_commands']['optimize'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Optimize tables'),
      '#submit' => [[$this, 'optimizeTablesSubmit']],
    );

    $form['optimize_table'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Optimization settings database'),
    );

    $last_optimization = $config->get('last_optimization');

    $form['optimize_table']['optimization_period'] = array(
      '#type' => 'select',
      '#title' => $this->t('Receive notification of the need to optimize the database, every.'),
      '#description' => $this->t('Last run: @date ago.', array(
        '@date' => _optimizedb_date($last_optimization),
      )),
      '#default_value' => $config->get('optimization_period'),
      '#options' => [
        0 => $this->t('Disabled'),
        1 => $this->t('@count day', ['@count' => 1]),
        2 => $this->t('@count day', ['@count' => 2]),
        7 => $this->t('@count days', ['@count' => 7]),
        14 => $this->t('@count days', ['@count' => 14]),
        30 => $this->t('@count days', ['@count' => 30]),
        60 => $this->t('@count days', ['@count' => 60]),
      ],
    );

    $size_tables = format_size($config->get('tables_size'));

    $form['optimize_table']['tables'] = array(
      '#type' => 'item',
      '#title' => $this->t('Current information on all database tables.'),
      '#markup' => $this->t('The size of all tables in the database: <b>@size</b>. View the size of the tables separately, you can on the page - <a href="@url">List of tables in the database</a>.', array(
        '@size' => $size_tables,
        '@url' => Url::fromRoute('optimizedb.list_tables')->toString(),
      )),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['optimizedb.settings'];
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
    $this->config('optimizedb.settings')
      ->set('optimization_period', $form_state->getValue('optimization_period'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Optimize all tables in database.
   */
  public function optimizeTablesSubmit(array &$form, FormStateInterface $form_state) {
    // Get all tables list.
    $tables = _optimizedb_tables_list();

    // Value is key.
    array_walk($tables, function(&$value) { $value = $value['name']; });

    _optimizedb_list_tables_operation_execute($tables, 'OPTIMIZE TABLE');
  }

}
