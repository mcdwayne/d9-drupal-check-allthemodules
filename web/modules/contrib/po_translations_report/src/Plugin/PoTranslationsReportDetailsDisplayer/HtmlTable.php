<?php

/**
 * @file
 * Contains \Drupal\po_translations_report\Plugin\PoTranslationsReportDetailsDisplayer\HtmlTable.
 */

namespace Drupal\po_translations_report\Plugin\PoTranslationsReportDetailsDisplayer;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\po_translations_report\DetailsDisplayerPluginBase;

/**
 * Provides HtmlTable.
 *
 * @PoTranslationsReportDetailsDisplayer(
 *   id = "html_table",
 *   label = @Translation("Html Table"),
 *   description = @Translation("Displays an html table of details."),
 * )
 */
class HtmlTable extends DetailsDisplayerPluginBase {

  /**
   * Renders results in form of an HTML table.
   *
   * @param array $details_array
   *   Array of details of a po file for a category.
   *
   * @return string
   *   HTML table represented results.
   */
  public function display(array $details_array) {
    // Start by defining the header.
    $header = array(
      array('data' => t('Source'), 'field' => 'source', 'sort' => 'asc'),
      array('data' => t('Translation'), 'field' => 'translation'),
    );
    // Get selected order from the request or the default one.
    $order = tablesort_get_order($header);
    // Get the field we sort by from the request if any.
    $sort = tablesort_get_sort($header);
    // Honor the requested sort.
    // Please note that we do not run any sql query against the database. The
    // 'sql' key is simply there for tablesort needs.
    $rows_sorted = $this->getResultsSorted($details_array, $order['sql'], $sort);

    // Display the details results.
    $display = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows_sorted,
    );

    return $display;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['html_table'] = array(
      '#type' => 'markup',
      '#markup' => $this->t('No configuration needed for this details display method.'),
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

}
