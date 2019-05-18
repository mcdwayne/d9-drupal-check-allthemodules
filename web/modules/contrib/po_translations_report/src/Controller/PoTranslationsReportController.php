<?php

/**
 * @file
 * Contains \Drupal\po_translations_report\Controller\PoTranslationsReportController.
 */

namespace Drupal\po_translations_report\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\po_translations_report\PoReporter;
use Drupal\po_translations_report\PoDetailsReporter;
use Drupal\po_translations_report\DisplayerPluginManager;
use Drupal\po_translations_report\DetailsDisplayerPluginManager;

/**
 * PoTranslationsReportController class.
 */
class PoTranslationsReportController extends ControllerBase {

  /**
   * Raw results in a form of a php array.
   *
   * @var array
   */
  protected $reportResults = array();

  /**
   * PoReporter service.
   *
   * @var Drupal\po_translations_report\PoReporter
   */
  protected $poReporter;

  /**
   * PoReporter service.
   *
   * @var Drupal\po_translations_report\PoDetailsReporter
   */
  protected $poDetailsReporter;

  /**
   * DisplayerPluginManager service.
   *
   * @var Drupal\po_translations_report\DisplayerPluginManager
   */
  protected $displayerPluginManager;

  /**
   * DetailsDisplayerPluginManager service.
   *
   * @var Drupal\po_translations_report\DetailsDisplayerPluginManager
   */
  protected $detailsDisplayerPluginManager;

  /**
   * Name of the config being edited.
   */
  const CONFIGNAME = 'po_translations_report.admin_config';

  /**
   * Constructor.
   *
   * @param PoReporter $po_reporter
   *   Reporter.
   * @param PoDetailsReporter $po_details_reporter
   *   Details reporter.
   * @param DisplayerPluginManager $displayer_plugin_manager
   *   Displayer plugin manager.
   * @param DetailsDisplayerPluginManager $details_displayer_plugin_manager
   *   Details displayer plugin manager.
   */
  public function __construct(PoReporter $po_reporter, PoDetailsReporter $po_details_reporter, DisplayerPluginManager $displayer_plugin_manager, DetailsDisplayerPluginManager $details_displayer_plugin_manager) {
    $this->poReporter = $po_reporter;
    $this->poDetailsReporter = $po_details_reporter;
    $this->displayerPluginManager = $displayer_plugin_manager;
    $this->detailsDisplayerPluginManager = $details_displayer_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('po_translations_report.po_reporter'),
        $container->get('po_translations_report.po_details_reporter'),
        $container->get('plugin.manager.po_translations_report.displayer'),
        $container->get('plugin.manager.po_translations_report.detailsdisplayer')
    );
  }

  /**
   * Displays the report.
   *
   * @return string
   *   HTML table for the results.
   */
  public function content() {
    $config = $this->config(static::CONFIGNAME);
    $folder_path = $config->get('folder_path');
    // If nothing was configured, tell the user to configure the module.
    if ($folder_path == '') {
      $url_path = Url::fromRoute('po_translations_report.admin_form');
      $url = \Drupal::l(t('configuration page'), $url_path);
      return array(
        '#type' => 'markup',
        '#markup' => t('Please configure a directory in @url.', array('@url' => $url)),
      );
    }
    $folder = new \DirectoryIterator($folder_path);
    $po_found = FALSE;
    foreach ($folder as $fileinfo) {
      if ($fileinfo->isFile() && $fileinfo->getExtension() == 'po') {
        $uri = $fileinfo->getRealPath();
        $subresults = $this->poReporter->poReport($uri);
        $this->setReportResultsSubarray($subresults);
        // Flag we found at least one po file in this directory.
        $po_found = TRUE;
      }
    }
    // Handle the case where no po file could be found in the provided path.
    if (!$po_found) {
      $message = t('No po was found in %folder', array('%folder' => $folder_path));
      drupal_set_message($message, 'warning');
    }

    // Now that all result data is filled, add a row with the totals.
    // Add totals row at the end.
    $this->addTotalsRow();

    $config = \Drupal::configFactory()->getEditable(static::CONFIGNAME);
    $displayer_plugin_id = $config->get('display_method');
    if ($displayer_plugin_id) {
      $results = $this->getReportResults();

      $configuration = $config->get($displayer_plugin_id . '_configuration');
      $displayer_plugin = $this->displayerPluginManager->createInstance($displayer_plugin_id, $configuration);

      $rendered = $displayer_plugin->display($results);
      return $rendered;
    }
    return array(
      '#type' => 'markup',
      '#markup' => '',
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
   * Getter for reportResults.
   *
   * @return array
   *   Reported results.
   */
  public function getReportResults() {
    return $this->reportResults;
  }

  /**
   * Adds a new po file reports as a subarray to reportResults.
   *
   * @param array $new_array
   *   Array representing a row data.
   * @param bool $totals
   *   TRUE when the row being added is the totals' one.
   */
  public function setReportResultsSubarray(array $new_array, $totals = FALSE) {
    if (!$totals) {
      $this->reportResults[] = $new_array;
    }
    else {
      $this->reportResults['totals'] = $new_array;
    }
  }

  /**
   * Adds totals row to results when there are some.
   */
  public function addTotalsRow() {
    $rows = $this->getReportResults();
    // Only adds total row when it is significant.
    if (!empty($rows)) {
      $total = array(
        'file_name' => \Drupal::translation()->formatPlural(count($rows), 'One file', '@count files'),
        'translated' => 0,
        'untranslated' => 0,
        'not_allowed_translations' => 0,
        'total_per_file' => 0,
      );
      foreach ($rows as $row) {
        $total['translated'] += $row['translated'];
        $total['untranslated'] += $row['untranslated'];
        $total['not_allowed_translations'] += $row['not_allowed_translations'];
        $total['total_per_file'] += $row['total_per_file'];
      }
      $this->setReportResultsSubarray($total, TRUE);
    }
  }

  /**
   * Route title callback.
   *
   * @param string $file_name
   *   The file name.
   * @param string $category
   *   The category.
   *
   * @return string
   *   The page title.
   */
  public function detailsTitle($file_name, $category) {
    // Get categories.
    $categories = $this->getAllowedDetailsCategries();
    if (in_array($category, array_keys($categories))) {
      // Get translated category label.
      $category = $categories[$category];
    }
    $title = $file_name . ' : [' . $category . ']';
    return Xss::filter($title);
  }

  /**
   * Displays string details per po file.
   *
   * @return string
   *   HTML table of details.
   */
  public function details($file_name, $category) {
    $config = $this->config(static::CONFIGNAME);
    $folder_path = $config->get('folder_path');
    $filepath = $folder_path . '/' . $file_name;
    $output = '';
    // Warn if file doesn't exist or the category is not known.
    if (!file_exists($filepath)) {
      $message = t('%file_name was not found', array('%file_name' => $file_name));
      drupal_set_message($message, 'error');
      return array(
        '#type' => 'markup',
        '#markup' => $output,
      );
    }
    if (!in_array($category, array_keys($this->getAllowedDetailsCategries()))) {
      $message = t('%category is not a known category', array('%category' => $category));
      drupal_set_message($message, 'error');
      return array(
        '#type' => 'markup',
        '#markup' => $output,
      );
    }
    $details_array = $this->poDetailsReporter->poReportDetails($filepath, $category);
    if (empty($details_array)) {
      return array(
        '#type' => 'markup',
        '#markup' => $output,
      );
    }
    else {
      $config = \Drupal::configFactory()->getEditable(static::CONFIGNAME);
      $details_displayer_plugin_id = $config->get('details_display_method');
      if ($details_displayer_plugin_id) {

        $configuration = $config->get($details_displayer_plugin_id . '_configuration');
        $details_displayer_plugin = $this->detailsDisplayerPluginManager->createInstance($details_displayer_plugin_id, $configuration);

        $rendered = $details_displayer_plugin->display($details_array);
        return $rendered;
      }
      return array(
        '#type' => 'markup',
        '#markup' => $output,
      );
    }
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

}
