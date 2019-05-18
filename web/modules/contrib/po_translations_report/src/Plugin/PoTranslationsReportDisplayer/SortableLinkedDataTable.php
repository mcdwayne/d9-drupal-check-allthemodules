<?php

/**
 * @file
 * Contains \Drupal\po_translations_report\Plugin\PoTranslationsReportDisplayer\SortableLinkedDataTable.
 */

namespace Drupal\po_translations_report\Plugin\PoTranslationsReportDisplayer;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\po_translations_report\DisplayerPluginBase;

/**
 * Provides SortableLinkedDataTable.
 *
 * @PoTranslationsReportDisplayer(
 *   id = "sortable_linked_data_table",
 *   label = @Translation("Sortable Linked Data Table"),
 *   description = @Translation("Displays a sortable html table with figures linked to details pages."),
 * )
 */
class SortableLinkedDataTable extends DisplayerPluginBase {

  /**
   * Renders results in form of sortable HTML table.
   *
   * @param array $results
   *   Array of details per po file.
   *
   * @see core/includes/sorttable.inc
   *
   * @return string
   *   HTML table represented results.
   */
  public function display(array $results) {
    // Get categories.
    $categories = $this->getAllowedDetailsCategries();
    // Start by defining the header with field keys needed for sorting.
    $header = array(
      array(
        'data' => t('File name'),
        'field' => 'file_name',
        'sort' => 'asc',
      ),
      array(
        'data' => $categories['translated'],
        'field' => 'translated',
      ),
      array(
        'data' => $categories['untranslated'],
        'field' => 'untranslated',
      ),
      array(
        'data' => $categories['not_allowed_translations'],
        'field' => 'not_allowed_translations',
      ),
      array(
        'data' => t('Total Per File'),
        'field' => 'total_per_file',
      ),
    );
    // Get selected order from the request or the default one.
    $order = tablesort_get_order($header);
    // Get the field we sort by from the request if any.
    $sort = tablesort_get_sort($header);

    // Honor the requested sort.
    // Please note that we do not run any sql query against the database. The
    // 'sql' key is simply there for tabelesort needs.
    $rows_sorted = $this->getResultsSorted($results, $order['sql'], $sort);
    $rows_linked = $this->linkifyResults($rows_sorted);
    $rows = $this->addCssClasses($rows_linked);

    // Display the sorted results.
    $display = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
    return $display;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['sortable_linked_data_table'] = array(
      '#type' => 'markup',
      '#markup' => $this->t('No configuration needed for this display method.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    );
    return $form;
  }

  /**
   * Helper method to restore allowed categories.
   *
   * @return array
   *   Array of allowed categories.
   */
  public function getAllowedDetailsCategries() {
    return array(
      'translated' => t('Translated'),
      'untranslated' => t('Untranslated'),
      'not_allowed_translations' => t('Not Allowed Translations'),
    );
  }

  /**
   * Sort the results honoring the requested order.
   *
   * @param array $results
   *   Array of results.
   * @param string $order
   *   The asked order.
   * @param string $sort
   *   The wanted sort.
   *
   * @return array
   *   Array of results.
   */
  public function getResultsSorted(array $results, $order, $sort) {
    if (!empty($results)) {
      // Obtain the column we need to sort by.
      foreach ($results as $key => $value) {
        $order_column[$key] = $value[$order];
      }
      // Sort data.
      if ($sort == 'asc') {
        array_multisort($order_column, SORT_ASC, $results);
      }
      elseif ($sort == 'desc') {
        array_multisort($order_column, SORT_DESC, $results);
      }
      // Always place the 'totals' key at the end.
      if (isset($results['totals'])) {
        $totals = $results['totals'];
        unset($results['totals']);
        $results['totals'] = $totals;
      }
    }
    return $results;
  }

  /**
   * Link all figures to the dedicated details page.
   *
   * @return array
   *   Sorted array of results.
   */
  public function linkifyResults($results) {
    if (!empty($results)) {
      foreach ($results as $key => &$result) {
        if ($key !== 'totals') {
          if ($result['translated'] > 0) {
            $route_params = array(
              'file_name' => $result['file_name'],
              'category' => 'translated',
            );
            $url_path = Url::fromRoute('po_translations_report.report_details', $route_params);
            $result['translated'] = \Drupal::l($result['translated'], $url_path);
          }
          if ($result['untranslated'] > 0) {
            $route_params = array(
              'file_name' => $result['file_name'],
              'category' => 'untranslated',
            );
            $url_path = Url::fromRoute('po_translations_report.report_details', $route_params);
            $result['untranslated'] = \Drupal::l($result['untranslated'], $url_path);
          }
          if ($result['not_allowed_translations'] > 0) {
            $route_params = array(
              'file_name' => $result['file_name'],
              'category' => 'not_allowed_translations',
            );
            $url_path = Url::fromRoute('po_translations_report.report_details', $route_params);
            $result['not_allowed_translations'] = \Drupal::l($result['not_allowed_translations'], $url_path);
          }
        }
      }
    }
    return $results;
  }

  /**
   * Adds css classes to results.
   *
   * @return array
   *   Linkified array of results.
   */
  public function addCssClasses($results) {
    if (!empty($results)) {
      foreach ($results as &$result) {
        foreach ($result as $result_key => &$result_value) {
          $result_value = array(
            'data' => $result_value,
            'class' => $result_key,
          );
        }
      }
    }
    return $results;
  }

}
