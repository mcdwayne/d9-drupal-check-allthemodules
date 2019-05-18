<?php

namespace Drupal\devel_contrib\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Show a form for outputting filtered portions of Views data.
 *
 * (The whole of the Views data array is too large to show at once with a tool
 * such as krumo.)
 */
class ViewsDataForm extends FormBase {

  public function getFormId() {
    return 'devel_views_data_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $query_table = \Drupal::request()->query->get('table');
    $query_filter = \Drupal::request()->query->get('filter');

    $views_data = \Drupal::service('views.views_data');
    $views_info = $views_data->get();
    $tables = array_keys($views_info);

    // Mystery empty key I don't have time to figure out.
    $tables = array_filter($tables);
    asort($tables);

    $options = array_combine($tables, $tables);

    $form['table'] = [
      '#title' => t('Table'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $query_table,
    ];

    $form['filter'] = [
      '#title' => t('Filter'),
      '#description' => t('Partial text match for table names. Overrides the list above.'),
      '#type' => 'textfield',
      '#default_value' => $query_filter,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    $form['#method'] = 'get';

    if (!empty($query_filter)) {
      $filtered_views_info_tables = preg_grep("@{$query_filter}@", $tables);
      $filtered_views_info = array_intersect_key($views_info, array_fill_keys($filtered_views_info_tables, TRUE));

      $form['data'] = \Drupal::service('devel.dumper')->exportAsRenderable($filtered_views_info);
    }
    elseif (isset($query_table)) {
      $form['data'] = \Drupal::service('devel.dumper')->exportAsRenderable($views_info[$query_table]);
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
